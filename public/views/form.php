<?php
/** @var array $formDatas */
/** @var array $classNames */
$classNames = isset($classNames) ? $classNames : [];
if (is_array($formDatas)) {
    $classNames[] = count($formDatas) > 1 ? 'multiple' : '';
}
?>
<div class="module-contact <?php echo implode(' ', $classNames); ?>">

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
                echo '<div class="contact-form-wrap">' . $formView->render($formViewOpts) . '</div>';
            }
        }
    }
    ?>

</div>
