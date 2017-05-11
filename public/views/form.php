<section class="module-contact">

    <?php if(!empty($notifications)){ echo implode("\n",$notifications); } ?>

    <?php
    /** @var \WonderWp\Framework\Form\FormViewInterface $formView */
    echo $formView->render($formViewOpts);
    ?>


</section>
