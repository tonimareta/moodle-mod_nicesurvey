/**
 * Get answers with conditions.
 *
 * @param {HTMLElement} question
 */
const getAnswers = question => {
    const answers = question.querySelectorAll('[name*="answer"]');

    if (answers && answers.length) {
        answers.forEach(answer => answer.addEventListener('change', () => getConditions(answer, question)));
    }
};

/**
 * Get realtions for question.
 *
 * @param {HTMLInputElement} answer
 * @param {HTMLElement} question
 */
const getConditions = (answer, question) => {
    const questionId = question.dataset.nsQuestionId;
    const conditions = document.querySelectorAll(`[data-ns-condition-id="${questionId}"]`);

    if (conditions && conditions.length) {
        conditions.forEach(condition => {
            if (answer.checked && answer.value === condition.dataset.nsConditionValue) {
                condition.classList.remove('d-none');
            } else {
                condition.classList.add('d-none');
            }
        });
    }
};

/**
 * Init main script.
 *
 * @returns {void}
 */
export const init = () => {
    const questions = document.querySelectorAll('[data-ns-question-id]');

    if (questions && questions.length) {
        questions.forEach(getAnswers);
    }
};