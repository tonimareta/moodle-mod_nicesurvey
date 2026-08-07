/**
 * Init results page.
 *
 * @returns {void}
 */
export const init = () => {
    const selectAll = document.getElementById('ns-delete-answers-select-all');
    const deleteAnswerForm = document.getElementById('ns-delete-answers-form');
    const deleteAnswerAction = document.getElementById('ns-delete-answer-action');

    if (deleteAnswerForm && deleteAnswerAction) {
        deleteAnswerAction.addEventListener('click', () => deleteAnswerForm.submit());
    }

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            document.querySelectorAll('[name="deleteanswers[]"]').forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        });
    }
};