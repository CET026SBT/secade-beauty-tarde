let currentStep = 1;

function goToStep(toStep, fromStep=currentStep) {
    if (toStep === fromStep) return;
    currentStep = toStep;

    const $fromStep = $(`#step-${fromStep}`);
    const $fromStepFields = $fromStep.find('.animated:not(button)');
    const $fromStepButtons = $fromStep.find('button');
    const $toStep = $(`#step-${toStep}`);
    const $toStepFields = $toStep.find('.animated:not(button)');
    const $toStepButtons = $toStep.find('button');
    const flow = (toStep < fromStep ? 'Right Left' : 'Left Right').split(' ');

    $fromStepFields.addClass(`fadeOut${flow[0]}`);
    $fromStepButtons.addClass('fadeOut');

    setTimeout(() => {
        $fromStep.addClass('d-none');
        $fromStepFields.removeClass(`fadeOut${flow[0]}`, `fadeInRight${flow[0]}`);
        $fromStepButtons.removeClass('fadeOut');
        $toStep.removeClass('d-none');
        $toStepFields.addClass(`fadeIn${flow[1]}`);
        $toStepButtons.addClass('fadeIn');
    }, 400);
}