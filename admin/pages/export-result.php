<?php
/** @var \WonderWp\Component\HttpFoundation\Result $uploadRes */
?>
<div class="export-result <?php echo ($uploadRes->getCode()==200) ? 'success' : 'error'; ?>">
    <?php
    if($uploadRes->getCode()==200){
        echo'<p>Pour télécharger votre export, <a href="'.$uploadRes->getData('file').'" target="_blank">cliquez ici</a></p>';
    } else {
        dump($uploadRes);
    }
    ?>
</div>
