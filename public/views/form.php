<div class="module-contact">

    <?php if(!empty($notifications)){ echo implode("\n",$notifications); } ?>

    <?php
    /** @var \WonderWp\Component\Form\FormViewInterface $formView */
    /** @var array $formViewOpts */
    if(!is_null($formView)) {
        echo $formView->render($formViewOpts);
    }
    ?>


</div>
