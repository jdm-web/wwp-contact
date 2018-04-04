<section class="module-contact">

    <?php if(!empty($notifications)){ echo implode("\n",$notifications); } ?>

    <?php
    /** @var \WonderWp\Component\Form\FormViewInterface $formView */
    /** @var array $formViewOpts */
    echo $formView->render($formViewOpts);
    ?>


</section>
