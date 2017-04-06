<section class="module-contact">

    <?php if(!empty($notifications)){ echo implode("\n",$notifications); } ?>

    <?php
    /** @var $formView \WonderWp\Framework\Form\FormViewInterface */
    echo $formView;
    ?>


</section>
