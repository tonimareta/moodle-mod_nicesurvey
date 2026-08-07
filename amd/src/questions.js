import {get_string as getMessage} from 'core/str';
import notification from 'core/notification';
import templates from 'core/templates';

/**
 * Add empty choice for question.
 *
 * @param {HTMLButtonElement} action
 */
const addChoice = action => {
    const container = action.closest('[data-ns-container="choices"]');
    const choices = container.querySelectorAll('[data-ns-choice-item]');

    if (choices && choices.length) {
        const lastChoiceItem = choices.length + 1;
        const mainChoice = choices[0];
        const cloned = mainChoice.cloneNode(true);
        const clonedLabel = cloned.querySelector('label');
        const clonedItem = cloned.querySelector(`input[type="hidden"]`);
        const clonedValue = cloned.querySelector(`input[type="text"]`);
        const removeButton = cloned.querySelector('[data-ns-choice-action="remove"]');

        getMessage('value', 'mod_nicesurvey')
            .then(message => {
                const actionContainer = action.closest('div.form-group');

                clonedLabel.innerText = message + ' ' + lastChoiceItem;
                clonedItem.name = `question[data][${lastChoiceItem}][item]`;
                clonedItem.value = lastChoiceItem;
                clonedValue.name = `question[data][${lastChoiceItem}][value]`;
                clonedValue.value = '';
                removeButton.classList.remove('disabled');
                removeButton.removeAttribute('disabled');
                removeButton.addEventListener('click', () => removeChoice(removeButton));
                container.insertBefore(cloned, actionContainer);
            })
            .catch(error => notification.exception(error));
    }
};

/**
 * Init question choices.
 *
 * @returns {void}
 */
const initChoices = () => {
    const actions = document.querySelectorAll('[data-ns-choice-action]');

    if (actions && actions.length) {
        actions.forEach(action => action.addEventListener('click', () => action.dataset.nsChoiceAction === 'add'
            ? addChoice(action)
            : removeChoice(action)
        ));
    }
};

/**
 * Load choices to question container.
 *
 * @param {Event} event
 */
const loadChoices = event => {
    const form = event.target.closest('form');
    const container = form.querySelector('[data-ns-container="choices"]');
    const datatype = form.querySelector('[name="question[datatype]"]');

    if (container && datatype && datatype.value) {
        container.innerHTML = '';

        if (datatype.value !== 'answer' && datatype.value !== 'text') {
            container.innerHTML = '<i class="fa fa-spin fa-spinner"></i>';

            templates
                .renderForPromise('mod_nicesurvey/choices', {
                    data: [{ item: 1, value: '', ismain: true }],
                    datalength: 1,
                    otherincluded: datatype.value.trim() === 'multichoice'
                }, '')
                .then(({html, js}) => {
                    container.innerHTML = '';
                    return templates.appendNodeContents(`#${container.id}`, html, js);
                })
                .then(initChoices)
                .catch(error => notification.exception(error));
            }
    }
};

/**
 * Load question condition data.
 *
 * @param {Event} event
 * @returns {boolean}
 */
const loadConditionData = event => {
    const form = event.target.closest('form');
    const conditionId = form.querySelector('[name="question[conditionid]"]');
    const conditionValue = form.querySelector('[name="question[conditionvalue]"]');

    if (!conditionId || !conditionValue) {
        return false;
    }

    conditionValue.value = '';
    conditionValue.innerHTML = '';

    if (!conditionId.value) {
        return false;
    }

    const conditionData = form.querySelectorAll(`[data-ns-condition="${conditionId.value}"]`);

    if (!conditionData || !conditionData.length) {
        return false;
    }

    conditionData.forEach(data => {
        const option = document.createElement('option');

        option.value = data.innerText;
        option.innerText = data.innerText;
        conditionValue.appendChild(option);
    });
};

/**
 * Remove choice from question container.
 *
 * @param {HTMLButtonElement} action
 */
const removeChoice = action => {
    const target = action.closest('div.form-group');

    if (target) {
        target.remove();
    }
};

/**
 * Init question edit page.
 *
 * @returns {void}
 */
export const init = () => {
    const datatypes = document.querySelectorAll('[name="question[datatype]"]');
    const conditions = document.querySelectorAll('[name="question[conditionid]"]');

    if (conditions && conditions.length) {
        conditions.forEach(condition => condition.addEventListener('change', loadConditionData));
    }

    if (datatypes && datatypes.length) {
        datatypes.forEach(datatype => datatype.addEventListener('change', loadChoices));
    }

    initChoices();
};