<?php
/** @var array $formDatas */
?>
<div class="module-contact <?php if(is_array($formDatas)){ echo count($formDatas) > 1 ? 'multiple' : ''; } ?>">

    <?php if (!empty($notifications)) {
        echo implode("\n", $notifications);
    } ?>

    <?php
    if (!empty($formDatas)) {
        foreach ($formDatas as $formData) {
            if (!is_null($formData['view'])) {
                /** @var \WonderWp\Component\Form\FormViewInterface $formView */
                $formView = $formData['view'];
                /** @var array $formViewOpts */
                $formViewOpts = !empty($formData['viewOpts']) ? $formData['viewOpts'] : [];
                echo '<div class="contact-form-wrap">'.$formView->render($formViewOpts).'</div>';
            }
        }
    }
    ?>

</div>
