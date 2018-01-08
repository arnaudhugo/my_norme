<?php
$way = $argv[1];
$listFile = array();

// Mettre dans un tableau tout les nom des fichier en fonction du repertoire
if (is_dir($way)) {
   if ($dh = opendir($way)) {
      while (($file = readdir($dh)) !== false) {
      	    if ($file != '.' && $file != '..') {
	       $extension = end(explode('.', $file));
	       if ($extension == "c" || $extension == "h") {
	       	  array_push($listFile, $file);
	       }
	    }
      }
      closedir($dh);
   }
}

// Verification
$nbError = 0;

for ($i = 0; $i < count($listFile); $i++) {
    $tmpL = 0;
    $fichier = $argv[1] . $listFile[$i];
    $ligne = file($fichier);
    echo "\nScan: $listFile[$i] \n";
    foreach ($ligne as $nbLigne => $contenuLigne) {
   	    $nbLigne = $nbLigne + 1;

	    /*
    	    if (preg_match('[d]', $contenuLigne)) {
	       echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : Contien : d \n";
	       $nbError++;
	    }*/


	    // Define dans un C
	    if (substr($listFile[$i], -1, 1) == "c" && preg_match('(define)', $contenuLigne)) {
	       echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : Define dans un C. \n";
	       $nbError++;
	    }
	    // =========

	    // Espace en fin de ligne
	    if (substr($contenuLigne, -2, 2) == " \n") {
	       echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : Espace en fin de ligne. \n";
	       $nbError++;
	    }
	    // ==========

	    // Double retour à la ligne
	    if ($contenuLigne == "\n" && $tmpL == 0) {
	       $tmpL = $nbLigne;
	       //$tmpL = $tmpL + 1;
	    }
	    if ($contenuLigne == "\n" && $tmpL == $nbLigne - 1) {
	       echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : Double retour à la ligne. \n";
	       $nbError++;
	       $tmpL = 0;
	    }
	    // ==========

	    // Ligne de plus de 80 caractères
	    if (strlen($contenuLigne) > 80) {
	       echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : Ligne de plus de 80 caractères. \n";
	       $nbError++;
	    }
	    // ==========

	    // Espace manquant après mot clé
	    if (preg_match('(\b(if|while|for|return)\()', $contenuLigne)) {
	       echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : Espace manquant après mot clé. \n";
	       $nbError++;
	    }
	    // ==========
    }
}
if ($nbError == 0) {
   echo "\033[32mOk\033[0m. \n";
} else {
  echo "Vous avez fait \033[31m$nbError\033[0m fautes de norme. \n";
}
?>