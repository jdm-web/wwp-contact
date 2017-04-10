<section class="module-contact">

    <?php if(!empty($notifications)){ echo implode("\n",$notifications); } ?>

    <?php
    /** @var \WonderWp\Framework\Form\FormViewInterface $formView */
    $formView->render($formViewOpts);
    ?>


</section>
