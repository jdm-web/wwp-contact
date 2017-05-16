<?php
/** @var \WonderWp\Framework\API\Result $uploadRes */
if($uploadRes->getCode()==200){
    echo'<p>Pour télécharger votre export, <a href="'.$uploadRes->getData('file').'" target="_blank">cliquez ici</a></p>';
} else {
    dump($uploadRes);
}
