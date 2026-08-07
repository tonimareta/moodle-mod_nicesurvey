<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

namespace mod_nicesurvey\output;

use coding_exception;
use core_text;
use core_useragent;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Nice Survey results renderer.
 *
 * @package    mod_nicesurvey
 * @copyright  2026 Anton Mareta
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class results_renderer {
    /**
     * Max width for cell.
     */
    const COL_WIDTH = 25;

    /**
     * Max file name length.
     */
    const MAX_FILE_NAME_LENGTH = 128; // Leave a reserve for the full path to file

    /**
     * Max worksheet tab name length.
     */
    const MAX_WORKSHEET_TAB_NAME_LENGTH = 28;

    /**
     * @var bool
     */
    public bool $anonymous = false;

    /**
     * @var array
     */
    public array $results = [];

    /**
     * @var string
     */
    public string $title = '';

    /**
     * @var array
     */
    public array $users = [];

    /**
     * @var Spreadsheet
     */
    protected Spreadsheet $spreadsheet;

    /**
     * Results renderer constructor.
     *
     * @param string $title
     * @param array $results
     * @param array $users
     * @param bool $anonymous
     */
    public function __construct(string $title, array $results, array $users, bool $anonymous = false) {
        $this->title = $title;
        $this->results = array_values($results);
        $this->users = array_values($users);
        $this->anonymous = $anonymous;
        $this->spreadsheet = new Spreadsheet();
    }

    /**
     * Render Excel document.
     *
     * @return void
     * @throws Exception
     * @throws coding_exception
     */
    public function render_xls(): void {
        $worksheet = $this->add_worksheet($this->title);
        $cell = $worksheet->getCell('A1');
        $column = $cell->getColumn();

        if (!$this->anonymous) {
            $this->write_text("{$column}1", get_string('userfullname', 'mod_nicesurvey'));
            $this->make_text_bold("{$column}1");
            $column++;
        }

        foreach ($this->results as $question) {
            $this->write_text("{$column}1", $question->title);
            $this->make_text_bold("{$column}1");
            $column++;
        }

        $row = 2;
        foreach ($this->users as $user) {
            $column = $worksheet->getCell("A{$row}")->getColumn();

            if (!$this->anonymous) {
                $this->write_text("A{$row}", $user->fullname);
                $this->make_text_bold("A{$row}");
                $column++;
            }

            foreach ($this->results as $question) {
                foreach ($question->answers as $answer) {
                    if (!empty($answer->users[$user->id])) {
                        $answertext = $answer->users[$user->id]->useranswer ?? '';
                        $this->write_text("{$column}{$row}", $answertext);
                    }
                }

                $column++;
            }

            $row++;
        }

        $this->set_columns_width(1, count($this->results) + 1);
        $this->spreadsheet->removeSheetByIndex(0);
        $this->send_file();
    }

    /**
     * Add worksheet to Excel document.
     *
     * @param string $title
     * @return Worksheet
     * @throws Exception
     */
    protected function add_worksheet(string $title): Worksheet {
        $title = strtr(trim($title, "'"), '[]*/\?:', '       ');
        $title = core_text::substr($title, 0, self::MAX_WORKSHEET_TAB_NAME_LENGTH) . '...';
        $title = trim($title, "'");

        $worksheet = new Worksheet($this->spreadsheet, $title);
        $worksheet->setPrintGridlines(false);
        $this->spreadsheet->addSheet($worksheet);

        $index = $this->spreadsheet->getActiveSheetIndex();
        $this->spreadsheet->setActiveSheetIndex($index + 1);

        return $worksheet;
    }

    /**
     * Cleans filename by removing suspicious or troublesome characters.
     *
     * @return string
     */
    protected function clean_filename(): string {
        $title = preg_replace('/\s+/', '_', $this->title);
        $date = date('Y-m-d');
        $filename = clean_filename($title);
        $filename = !is_string($filename) ? $date : $filename;

        if (core_text::strlen($filename) > self::MAX_FILE_NAME_LENGTH) {
            $filename = core_text::substr($filename, 0, self::MAX_FILE_NAME_LENGTH);
        }

        $filename = "{$filename}_{$date}.xlsx";

        return core_useragent::is_ie() || core_useragent::is_edge() ? rawurlencode($filename) : s($filename);
    }

    /**
     * Make text bold.
     *
     * @param string $coord
     * @return Font
     */
    protected function make_text_bold(string $coord): Font {
        return $this->spreadsheet
            ->getActiveSheet()
            ->getStyle($coord)
            ->getFont()
            ->setBold(true);
    }

    /**
     * Display Excel as HTML. For debug only.
     *
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    protected function print_html(): void {
        $writer = IOFactory::createWriter($this->spreadsheet, 'Html');
        header('Content-Type: text/html; charset=utf-8');
        $writer->save('php://output');
        exit();
    }

    /**
     * Send file to user.
     *
     * @throws Exception
     */
    protected function send_file(): void {
        foreach ($this->spreadsheet->getAllSheets() as $sheet) {
            $sheet->setSelectedCells('A1');
        }

        $this->spreadsheet->setActiveSheetIndex(0);
        ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $this->clean_filename() . '"');

        $writer = IOFactory::createWriter($this->spreadsheet, IOFactory::WRITER_XLSX);
        $writer->save('php://output');
        exit();
    }

    /**
     * Set columns width.
     *
     * @param int $col
     * @param int $lastcol
     * @return void
     * @throws Exception
     */
    protected function set_columns_width(int $col, int $lastcol) {
        for ($i = $col; $i <= $lastcol; $i++) {
            $this->spreadsheet
                ->getActiveSheet()
                ->getColumnDimensionByColumn($i)
                ->setWidth(self::COL_WIDTH)
                ->setVisible(true)
                ->setOutlineLevel(0);
        }
    }

    /**
     * Write text to Spreadsheet coordinate.
     *
     * @param string $coord
     * @param string $text
     * @return Worksheet
     * @throws Exception
     */
    protected function write_text(string $coord, string $text): Worksheet {
        $worksheet = $this->spreadsheet->getActiveSheet();
        $style = $worksheet->getStyle($coord);

        $style->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $style->applyFromArray(['alignment' => ['wrapText' => true]]);

        return $worksheet->setCellValueExplicit($coord, $text, DataType::TYPE_STRING);
    }
}