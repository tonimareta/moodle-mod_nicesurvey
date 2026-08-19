# Advanced Survey & Feedback Module for Moodle

Transform data collection and student feedback within your Moodle environment. The **Advanced Survey & Feedback Module** provides educators, administrators, and researchers with a powerful suite of tools to create customized surveys, analyze response trends visually, and export detailed reporting seamlessly.

---

## Table of Contents

- [Features](#features)
- [Primary Use Cases](#primary-use-cases)
- [System Requirements](#system-requirements)
- [Installation](#installation)
    - [Method 1: Manual Installation (Zip Upload)](#method-1-manual-installation-zip-upload)
    - [Method 2: Git Clone](#method-2-git-clone)

## Features

### Real-Time Visual Analytics & Graphing
* **Instant Charting:** Automatically generate clean, interactive visual graphs as responses come in.

### Seamless Excel Export
* **One-Click Export:** Download comprehensive raw response datasets directly to `.xlsx` format.
* **Formatted Spreadsheets:** Exported files maintain clean formatting, metadata headers, and structured tables ready for immediate analysis in Microsoft Excel, Google Sheets, or statistical software.

### Flexible Question Management
* **Diverse Question Types:** Supports one-choice, multiple-choice, short text answer, rating stars, dropdowns and open-ended text fields.
* **Customizable Structure:** Simple question reordering, conditional question logic.
* **Reusable Templates:** Save survey configurations as templates to deploy standard evaluations across multiple courses effortlessly.

### Anonymous Submission Controls
* **Privacy Assurance:** Fully configurable anonymity settings to protect respondent identities and ensure candid, uninhibited feedback.

### Date-Driven Availability & Scheduling
* **Time-Bound Campaigns:** Set precise opening and closing dates and times for survey availability.

---

## Primary Use Cases

* **End-of-Semester Course Evaluations:** Gather student feedback on course structure, instructors, and learning materials.
* **Institutional Research & Surveys:** Conduct campus-wide or organizational research with strict anonymity and time constraints.
* **Knowledge Check-ins & Pulse Surveys:** Deploy quick weekly polls to measure student understanding and engagement levels.

---

## System Requirements

| Feature | Details                       |
| :--- |:------------------------------|
| **Moodle Compatibility** | Moodle 4.5+                   |
| **PHP** | 8.1+                          |
| **Browser** | Latest browser                |

## Installation

### Method 1: Manual Installation (Zip Upload)
1. Download the latest release `.zip` package from the [Releases page](../../releases).
2. Log in to your Moodle site as an Administrator.
3. Navigate to **Site Administration > Plugins > Install plugins**.
4. Upload the plugin ZIP file into the **ZIP package** area.
5. Click **Install plugin from the ZIP file** and follow the on-screen database upgrade steps.

### Method 2: Git Clone
Navigate to your Moodle installation's `mod` directory and clone this repository:

```bash
cd /path/to/moodle/mod
git clone https://github.com/tonimareta/moodle-mod_nicesurvey nicesurvey
```