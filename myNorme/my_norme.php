<?php
$listFile = array();

// Mettre dans un tableau tout les nom des fichier en fonction du repertoire
if (!empty($argv[1]) && is_dir($argv[1])) {
   $way = $argv[1];
      if ($dh = opendir($way)) {
      	 while (($file = readdir($dh)) !== false) {
      	       if ($file != '.' && $file != '..') {
	       	  $t = explode('.', $file);
	       	  $extension = end($t);
	       	  if ($extension == "c" || $extension == "h") {
	       	     array_push($listFile, $file);
	       	  }
	       }
      	 }
      	 closedir($dh);
	 start_scan($listFile, $way);
      }
} else {
  echo "\033[31mErreur mauvais chemin.\033[0m \n";
}

// Verification
function start_scan($listFile, $way) {

	 $nbError = 0;
	 $nbDossier = 0;

	 for ($i = 0; $i < count($listFile); $i++) {
	     $inFunc = FALSE;
	     $ligneInFunc = 0;
	     $nbFunc = 0;
	     $tmpL = 0;
    	     $fichier = $way . $listFile[$i];
	     $ligne = file($fichier);
    	     echo "\nScan: $listFile[$i] \n";
    	     foreach ($ligne as $nbLigne => $contenuLigne) {
   	     $nbLigne = $nbLigne + 1;

	     /*
    	     if (preg_match('[d]', $contenuLigne)) {
	       	echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : Contien : d \n";
	       	$nbError++;
	     }*/

	     // ===== Define dans un C
	     if (substr($listFile[$i], -1, 1) == "c" && preg_match('(#define)', $contenuLigne)) {
	       	echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : Define dans un C. \n";
	       	$nbError++;
	     }
	     // =========

	     // ===== Espace en fin de ligne
	     if (substr($contenuLigne, -2, 2) == " \n") {
	       	echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : Espace en fin de ligne. \n";
	       	$nbError++;
	     }
	     // ==========

	     // ===== Double retour à la ligne
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

	    // ===== Ligne de plus de 80 caractères
	    if (strlen($contenuLigne) > 80) {
	       echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : Ligne de plus de 80 caractères. \n";
	       $nbError++;
	    }
	    // ==========

	    // ===== Espace manquant après mot clé
	    if (preg_match('(\b(if|while|for|return)\()', $contenuLigne)) {
	       echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : Espace manquant après mot clé. \n";
	       $nbError++;
	    }
	    // ==========

	    // ===== Espace manquant après virgule
	    preg_match_all('/,./', $contenuLigne, $matches, PREG_SET_ORDER);

	    if (!empty($matches)) {
	       for ($a = 0; $a < count($matches); $a++) {
	       	   if ($matches[$a][0] != ", ") {
		      echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : Espace manquant après virgule. \n";
		      $nbError++;
		   }
	       }
	    }
	    // ==========

	    // ===== Mauvais header / Triche Edouard MOULINETTE
	    if ($nbDossier != sizeof($listFile)) {
	       if ($nbLigne >= 3 && $nbLigne <= 10) {
	       	  if (preg_match('(\/\/)', $contenuLigne) == 1) {
		     if (preg_match('(moulin_e@etna-alternance.net)', $contenuLigne)) {
		     	echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : TRICHE (moulin_e@etna-alternance.net). \n";
			$nbError++;
		     }
		     if (preg_match('(MOULINETTE Edouard)', $contenuLigne)) {
		     	echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : TRICHE (MOULINETTE Edouard). \n";
			$nbError++;
		     }
		  }
		  else {
		       echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : Header oublié. \n";
		       $nbError++;
		  }
	       }
	    }
	    // =========
	    
	    // ===== Fonctions de plus de 25 lignes & Nombre de fonctions par fichier
	    if (preg_match('/^{/', $contenuLigne) && $inFunc == FALSE) {
	       $inFunc = TRUE;
	    }
	    elseif (preg_match('/^}/', $contenuLigne)) {
	    	   $inFunc = FALSE;
		   $nbFunc = $nbFunc + 1;
		   $ligneInFunc = 0;
	    }
	    else {
	    	 if ($inFunc == TRUE) {
		    $ligneInFunc = $ligneInFunc + 1;
		 }
		 if ($ligneInFunc > 25) {
		    echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : Fonctions de plus de 25 lignes. \n";
		    $nbError++;
		 }
	    }
	    // ==========

	    // ===== Plus de 4 paramètres pour une fonction
	    $nbParam = explode(',', $contenuLigne);
	    if (count($nbParam) > 4) {
	       echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : Plus de 4 paramètres pour une fonction. \n";
	       $nbError++;
	    }
	    // ==========
    }
    // ===== Show NbFunc
    if ($nbFunc > 5) {
       echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : Il y a $nbFunc function dans le fichier. \n";
       $nbError++;
       $nbFunc = 0;
    }
    // ==========
}
if ($nbError == 0) {
   echo "\033[32mOk\033[0m. \n";
} else {
  echo "Vous avez fait \033[31m$nbError\033[0m fautes de norme. \n";
}
}
?>