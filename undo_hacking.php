<?php

	set_time_limit(0);

 
	$version = "1.9.0";
	$cont_fic = 0;
	$msg = "<html><head><title>Check Archivos web</title><link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css'><link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap-theme.min.css'><script src='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js'></script></head><body style=\"font-family:Verdana; font-size:11px;\">";
	$ssw = 0;
	$hora_ini = strtotime(date ("F d Y H:i:s."));
	$parametros = "";

	/*********************************************************/
	// CABECERA DE CONFIGURACION --> PHP MAILER
	
	//Hay que incluid las clase PHPMailes
	//Puedes Descargar de https://github.com/PHPMailer/PHPMailer
	$mail = new phpmailer();
	$mail->PluginDir = "";
	$mail->Mailer = "smtp";
	$mail->Host = "smtp.pruebas.es";
	$mail->Port="110";
	$mail->SMTPAuth = true;
	$mail->Username = "pruebas@pruebas.com";
	$mail->Password = "Password";
	

	/*****************************************************/
	// COMPROBAMOS LA INFORMACION SOBRE LOS DIRECTORIOS 
	
	$path = $_SERVER['SCRIPT_FILENAME'];
	$data["exists"] = is_file($path);// Comprobamos si el fichero es escribible
  	$data["writable"] = is_writable($path);// Leemos los permisos del fichero
  	$data["chmod"] = ($data["exists"] ? substr(sprintf("%o", fileperms($path)), -4) : FALSE);// Extraemos la extensión, un sólo paso
  	$data["ext"] = substr(strrchr($path, "."),1);// Primer paso de lectura de ruta
  	$data["path"] = array_shift(explode(".".$data["ext"],$path));// Primer paso de lectura de nombre
  	$data["name"] = array_pop(explode("/",$data["path"]));// Ajustamos nombre a FALSE si está vacio
  	$data["name"] = ($data["name"] ? $data["name"] : FALSE);// Ajustamos la ruta a FALSE si está vacia
  	$data["path"] = ($data["exists"] ? ($data["name"] ? realpath(array_shift(explode($data["name"],$data["path"]))) : realpath(array_shift(explode($data["ext"],$data["path"])))) : ($data["name"] ? array_shift(explode($data["name"],$data["path"])) : ($data["ext"] ? array_shift(explode($data["ext"],$data["path"])) : rtrim($data["path"],"/")))) ;
  	$data["filename"] = (($data["name"] OR $data["ext"]) ? $data["name"].($data["ext"] ? "." : "").$data["ext"] : FALSE);// Devolvemos los resultados
	$current_dir = $data["path"]."/";
	
  
	/*********************************************/
	// ESTA ES LA LLAMADA A LA FUNCION PRINCIPAL
	find_files($current_dir);
	
	
	/*********************************************************/
	// MENSAJE --> PHP MAILER
	/*Indicamos cual es nuestra dirección de correo y el nombre que
	queremos que vea el usuario que lee nuestro correo*/
	$mail->From = "pruebas@pruebas.com";
	$mail->FromName = "Control Web";
	//El valor por defecto de Timeout es 10, le voy a dar un poco mas
	$mail->Timeout=30;
	//Indicamos cual es la dirección de destino del correo.
	$mail->AddAddress('"pruebas@pruebas.com";');
	$mail->AddBCC("pruebas@pruebas.com");
	
	$mail->Subject = "(".$_SERVER['HTTP_HOST'].") Archivos --> ".$ssw;
	//Cuerpo del mensaje. Puede contener html
	$mail->Body = $msg;
	$mail->AltBody = $msg;

	$mail->isSendMail();
	
	/* COMPROBAMOS QUE ACCION QUEREMOS REALIZAR
	, MOSTRAR EN PANTALLA O ENVIAR CORREO*/
	if(isset($_GET['show'])&&$_GET['show']=='1') {
		echo $msg;
	}elseif(isset($_GET['show'])&&$_GET['show']=='0') {
		$resultado = $mail->Send();
	}else{
		if($ssw > 0){
			$resultado = $mail->Send();
		}
	}
	
	
/*************************************************************************************************************************************************************
**************************************************************************************************************************************************************
**************************************************************************************************************************************************************/

	/*********************************************************
	******    FUNCIONES find_files()
	**********************************************************/
	function find_files($seed) {
		global $mail;
		global $ssw;
		global $msg;
		global $hora_ini;
		

		if(! is_dir($seed)) { 
			$msg .= 'No directory found at:' . $seed;
			return false;
		}
		$files = array();
		$dirs = array($seed);

		$msg .= "<br /><br />";
		$msg .= "<table class='table table-bordered table-hover table-condensed'>";

		while(NULL !== ($dir = array_pop($dirs))) {   
		   if($dh = opendir($dir)) {
				while( false !== ($file = readdir($dh))) {
					//Se añade el nombre del archivo, para que no lo envie como fichero peligroso.
					if($file == '.' || $file == '..' || $file == 'test_xxx.php')continue;
						$path = $dir . '/' . $file;
						//Se comprueba que sea un directorio, si lo es se guarda
						if(is_dir($path)){
							$dirs[] = $path; 
						}else{
							
							if(preg_match('/^.*\.(php[\d]?|js|htaccess|css)$/i', $path)){ 
								check_files($path); 
							}
						} 
				}
				closedir($dh);
			}
		}

		$msg .= '</table >';	

		
		//CREAMOS LA LEYENDA PARA EL CORREO
		$hora_fin = strtotime(date ("F d Y H:i:s."));
		$tiempo_ejec = $hora_fin - $hora_ini;
		tabla_leyenda($tiempo_ejec);
					
	}
	/**********************************************************************************************/
	/**************************************** FIN FUNCION *****************************************/

	
	
	/************************************************************************************************
	******    FUNCIONES check_files
	* Se le añaden al array $str_to_find las funciones que se quiere que se encuentren como
	*    por ejemplo: "base64_decode", "djeu84m"
	*    Se quedan comentadas: Fucniones de alto Riesgo
	* Se crea este array que contiene, los archivos de Joomla que contienen estas funciones, para que no los cuente como malos.
	*    Potencialmente peligrosas, creo que son útiles para el funcionamiento del sistema.
	************************************************************************************************/	
	function check_files($this_file) {
		global $ssw;
		global $msg;
		global $parametros;
		
		//CADENAS A BUSCAR --> 
		$str_to_find[]='base64_decode';
		$str_to_find[]='djeu84m';
		//$str_to_find[]='system';				- esta en demasiados archivos de joomla para tener control
		//$str_to_find[]='preg_replace'; 		- esta en demasiados archivos de joomla para tener control
		$str_to_find[]='GIF89a1';
		$str_to_find[]='iskorpitx';
		
		
		
		//ARCHIVOS COMUNES DE JOOMLA -->
		$common[]='com_content/controller.php';
		$common[]='com_mailto/controller.php';
		$common[]='com_user/controller.php';
		$common[]='com_content/controllers/article.php';
		$common[]='com_content/models/form.php';
		$common[]='com_users/controllers/user.php';
		$common[]='com_users/models/login.php';
		$common[]='administrator/components/com_templates/controllers/source.php';
		$common[]='administrator/components/com_templates/models/source.php';
		$common[]='administrator/components/com_phocadownload/helpers/fileupload.php';
		$common[]='geshi/php.php';
		$common[]='geshi/php-brief.php';
		$common[]='/com_weblinks/controllers/weblink.php';
		$common[]='/com_weblinks/models/form.php';
		$common[]='/libraries/simplepie/simplepie.php';
		$common[]='/libraries/phpxmlrpc/xmlrpc.php';
		$common[]='/administrator/components/com_media/controllers/file.php';
		$common[]='/administrator/components/com_login/models/login.php';
		$common[]='/administrator/components/com_media/controllers/file.php';
		$common[]='/administrator/components/com_joomlaupdate/restore.php';
		$common[]='/administrator/components/com_menus/controllers/item.php';
		$common[]='/administrator/components/com_users/models/users.php';
		$common[]='/administrator/components/com_phocamaps/helpers/phocamapsmap.php';
		$common[]='/administrator/components/com_phocamaps/helpers/phocamaps.php';
		$common[]='plugins/system/highlight/highlight.php';
		$common[]='/check.php';
		$common[]='components/com_ajaxregistration/controller.php';
		$common[]='/plugins/content/geshi/geshi/geshi/php-brief.php';
		$common[]='/plugins/content/geshi/geshi/geshi/php.php';
		$common[]='/modules/mod_stats/helper.php';
		$common[]='/plugins/authentication/gmail/gmail.php';
		$common[]='/libraries/simplepie/simplepie.php';
		$common[]='/libraries/joomla/language/language.php';
		$common[]='/libraries/joomla/http/transport/curl.php';
		$common[]='/libraries/joomla/application/daemon.php';
		$common[]='/components/com_jce/editor/tiny_mce/plugins/spellchecker/classes/googlespell.php';
		$common[]='/components/com_jce/editor/tiny_mce/plugins/spellchecker/classes/pspellshell.php';	
		
		
		/* Obtiene el parametro DELETE
		*     - 0: No realiza ninguna accion
		*     - 1: Elimina los ficheros que esten contenido en el array $deletefile de la funcion delete_the_file()
		*     Los ficheros eliminados se mostraran en pantalla en filas de color amarillo
		*/    
		$delete_files = 0; //Se inicializa a "0"
		if(isset($_GET['delete'])&&$_GET['delete']=='1') {
			$delete_files = 1;
		}else{
			$delete_files = 0;
		}

				
		/* Obtiene el parametro COMMONS
		*      - 0: No realiza ninguna accion
		*      - 1: Sacara por pantalla los archivos del sistema de JOOMLA que estan en el array y sean
		*           detectados que tienen funciones maliciosas.
		*/
		$show_commons = 1; //Se inicializa a "1"
		if(isset($_GET['commons'])&&$_GET['commons']=='0') {
			$show_commons = 0;
		}else{
			$show_commons = 1;
		}

		

		/* Obtiene el parametro DAYS
		*       - INT: Numero de dias en los que el Script va a mirar si se han realizado cambios en los ficheros
		*/
		$filter_by_days = 0;
		if(isset($_GET['days'])&&$_GET['days']) {
			$days = (int) $_GET['days'];
			$filter_by_days = 1;
		}else{
			if(isset($_GET['show'])&&$_GET['show']=='1') {
				$filter_by_days = 0;			
			}elseif(isset($_GET['show'])&&$_GET['show']=='0') {
				$filter_by_days = 0;		
			}else{
				$days = 20;
				$filter_by_days = 1;			
			}
		}

		// .................. show check manual files true/false GET['manual']=1/0 / default = 1
		/* Obtiene el parametro MANUAL
		*       - 0: Nunca
		*       - 1: Siempre tiene que estar a 1 para que nos diga que fichero no ha podido controlar y
		*            hay que mirarlos manualmente.
		*/
		$manual = 1;
		if(isset($_GET['manual'])&&$_GET['manual']=='0'){
			$manual = 0;
		}else{
			$manual = 1;
		}

		// .................. calculate days diff between today and file date
		$alertday = 0;
		$fecha =  date("F d Y", filemtime($this_file));
		$ts1 = strtotime(date ("F d Y H:i:s.", filemtime($this_file)));
		$ts2 = strtotime(date ("F d Y H:i:s."));
		$seconds_diff3 = $ts2 - $ts1;
		$seconds_diff = floor($seconds_diff3/3600/24);
		$seconds_diff2 = (int) floor($seconds_diff3/3600/24);
		if($seconds_diff<7)  $alertday = 1;

		/* 
		*  Estamos filtrando por  la fecha de actualizacion de los archivos y la Fecha de hoy
		*  la diferencia de esa fecha con la de hoy no puede ser superior al parametro DAYS
		*/
		//Si tenemos Parametro DAYS
		if($filter_by_days){ 
			Control_htaccess($this_file, $seconds_diff, '', $alertday, '', $delete_files);
			if($days>=$seconds_diff2)  { //Si los dias son inferiores a la fecha de actualización "diff > show file"
				/********     PINTA LA TABLA    *******/
				print_record_table($this_file, $seconds_diff, 'recent', $alertday, '', $delete_files);	
			}
		}else{ 
			if(is_readable($this_file)){
				// Si NO es posible mirar dentro del Fichero, nos marca en la tabla la fila con color GRIS
				if(!($content = file_get_contents($this_file))&&$manual) {  
					/********     PINTA LA TABLA    *******/
					print_record_table($this_file, $seconds_diff, 'manual', $alertday, '', $delete_files);	
				}else{
					$pintado = false;
					// Es posible mirar dentro del contenido
					while(list(,$value)=each($str_to_find)) {
						
						//Buscamos las funciones peligrosas
						if(stripos($content, $value) !== false) {
							$type = 'danger';
							//Comprobamos que no esten en los ficheros de JOOMLA
							while(list(,$valuecommon)=each($common)) {  
								if (stripos($this_file, $valuecommon) !== false) {
									$type = 'common';
									//Si esta marcado para que no muestre los archivos de JOOMLA
									if(!$show_commons) return; //Hace retunr para no mostrar los archivos.
									break;
								}
							}
							/********     PINTA LA TABLA    *******/
							print_record_table($this_file, $seconds_diff, $type, $alertday, $value, $delete_files);
						}else{
							if($pintado==true) return;
							Control_htaccess($this_file, $seconds_diff, '', $alertday, '', $delete_files);
							$pintado=true;
						}	
					}
				}
			}else{
				//No tiene permisos de lectura
				print_record_table($this_file, $seconds_diff, "denied", $alertday, '', $delete_files);
			}
		}
	  	unset($content);
	}
	/**********************************************************************************************/
	/**************************************** FIN FUNCION *****************************************/
	

	/*********************************************************
	******    FUNCIONES delete_the_file()
	**********************************************************/
	function Control_htaccess($this_file, $seconds_diff, $type, $alertday, $value, $delete_files) {
		
		/* Obtiene el parametro DELETEHTACCESS
		*     - 0: No realiza ninguna accion
		*     - 1: Elimina los ficheros que sean htaccess
		*/    
		$delete_htaccess = 0; //Se inicializa a "0"
		if(isset($_GET['delete_htaccess'])&&$_GET['delete_htaccess']=='1') {
			$delete_htaccess = 1;
		}else{
			$delete_htaccess = 0;
		}	
		
		$re1='.*?';	
		$re2='(htaccess)';
		//CONTROL DEL ARCHIVO HTACCESS
		if($c=preg_match_all ("/".$re1.$re2."/is", $this_file, $matches)){
			//ELIMINA EL FAMOSO VIRUS HTACCESS
			if($delete_htaccess==1){
				//ESCRIBIMOS SUS DATOS
				print_record_table($this_file, $seconds_diff, 'deleted', $alertday, '', 0);
				//ELIMINA LOS ARCHIVOS
				if (file_exists($this_file)){unlink($this_file);}
			}else{
					print_record_table($this_file, $seconds_diff, "htaccess", $alertday, '', $delete_files);
			}
		}				
	
		return;
	}
	/**********************************************************************************************/
	/**************************************** FIN FUNCION *****************************************/	
	

	/************************************************************************************************
	******    FUNCIONES print_record_table()
	* Imprime la tabla con los resultados, la leyenda con los resultados en la siguiente:
	*     - Eliminado: Archivo eliminado por estar en el array de eliminacion
	*     - PELIGRO MODIFICADO: Este en caso de Virus es el mas tipico, en el que le meten algun tipo de Script.
	*     - Mirar contenido: Este salta cuando no se tiene acceso al archivo, por ejemplo cuando se le cambian los permisos
	*     - FUNCION PELIGROSA: Este salta cuando dentro del contenido de uno de los ficheros se encuentra una funcion peligrosa
	*     - JOOMLA FILE: Archivo de Joomla pero con contenido que puede ser peligroso, por ahora no se tiene en cuenta.
	*   Hay que tener en cuenta en esta funcion, la variable "ssw", que contendra segun mi criterio el numero de 
	* archivos peligrosos detectados, no todos los tipos suman, solo suman: PELIGRO MODIFICADO, Mirar Contenido:... y FUNCION PELIGROSA.
	************************************************************************************************/
	function print_record_table($this_file, $seconds_diff, $type, $alertday, $value, $delete_files) {
		global $ssw;
		global $msg;
		global $cont_fic;
		
		global $current_dir;
		
		if (!file_exists($this_file)){return;}
		switch ($type) {
			case 'deleted':
				$style= '';
				$text = 'Archivo ELIMINADO';
				$text_s = 'Eliminado';
				break;
			case 'recent':
				$ssw++;
				$style= 'class="danger"';
				$text = 'PELIGRO MODIFICADO';
				$text_s = '<b>PELIGRO MODIFICADO</b>';
				break;
			case 'manual':
				$ssw++;
				$style= 'class="active"';
				$text = '<b>Manual check</b>';
				$text_s = 'Mirar Contenido:<br /><b>No se puede leer</b>';
				break;
			case 'danger':
				$ssw++;
				$style= 'class="success"';
				$text = 'Contiene <b>'.strtoupper($value).'</b>';
				$text_s = '<b>FUNCION PELIGROSA</b>';
				break;	
			case 'common':
				$style= ' class="warning"';
				$text = 'Contiene '.strtoupper($value);
				$text_s = 'JOOMLA FILE';
				break;	
			case 'htaccess':
				$ssw++;
				$style= '';
				$text = 'PELIGRO HTACCESS';
				$text_s = '<b>PELIGRO HTACCESS</b>';
				break;	
			case 'denied':
				$ssw++;
				$style= ' class="info"';
				$text = 'Permiso denegado';
				$text_s = 'Permiso denegado';
				break;			
		}

		$style2 = $style;

		if($alertday) $style2 = 'style="border:#000 1px solid;"';

		
		$cont_fic++;

		$msg .= '
			<tr>
				<td '.$style.'><b> '.$cont_fic.' </b></td>
				<td '.$style.'>'.$text_s.'</td>
				<td '.$style2.'><b>'.$seconds_diff.'</b></td>
				<td '.$style.'>' .date ("d-m-Y H:i:s", filemtime($this_file)).'</td>
				<td '.$style.'>'.str_ireplace($current_dir, "", $this_file).'</td>
				<td '.$style.'>'.$text.'</td>
				<td '.$style.'>'.substr(decoct(fileperms($this_file)),3).'</td>		
			
			</tr>';

		//Si se quiere que se eliminen archivos de manera automatica, se llama a esta funcion.
		if($delete_files) delete_the_file($this_file, $seconds_diff, $type, $alertday, $value, 0);

		return;

	}
	/**********************************************************************************************/
	/**************************************** FIN FUNCION *****************************************/
	


	/*********************************************************
	******    FUNCIONES delete_the_file()
	**********************************************************/
	function delete_the_file($this_file, $seconds_diff, $type, $alertday, $value, $delete_files) {
		global $ssw;
		
		/* Este array contiene, directorios y archivos que en cuento se encuentren, hay que eliminarlos.
		*   - Esta funcion contiene por defecto estos ficheros, que no tiene porque ser los buenos. 
		*/
		
		//$deletefiles[] = '/cache/';
		$deletefiles[] = 'index_backup.php';

		

		while(list(,$valuedelete)=each($deletefiles)) {
			if (stripos($this_file, $valuedelete) !== false) {
				//ESCRIBIMOS SUS DATOS
				print_record_table($this_file, $seconds_diff, 'deleted', $alertday, '', 0);
				//ELIMINA LOS ARCHIVOS
				unlink($this_file);
				return;
			}
		}
		return;
	}
	/**********************************************************************************************/
	/**************************************** FIN FUNCION *****************************************/

	
	/*********************************************************
	******    FUNCIONES tabla_leyenda()
	**********************************************************/
	function tabla_leyenda($time_dif) {
		global $ssw;
		global $msg;
		global $parametros;
		global $version;
		
	
		$aux = '<table class="table table-bordered table-hover table-condensed">';
		$aux .= '<tr style="background-color:#AEC6CF;" >
					<th>CODIGO</th>
					<th>DESCRIPCION</th>
					<th>ACCION</th>
				</tr>';
		$aux .= '<tr>
					<td>Eliminado</td>
					<td>El archivo ha sido eliminado por estar configurado dentro de la lista de peligrosos<br />configurada en el codigo.</td>
					<td></td>
				</tr>';		
		$aux .= '<tr class="danger">
					<td>PELIGRO MODIFICADO</td>
					<td>Archivo potencialmente peligroso si no reconocemos la fecha de actualización.</td>
					<td>PELIGRO REVISION URGENTE</td>
				</tr>';	
		$aux .= '<tr class="active">
					<td>Mirar Contenido:<br /><b>No se puede leer</b></td>
					<td>Archivo bloqueado para lectura, revisar permisos y contenido si se desconoce la procedencia</td>
					<td>PELIGRO REVISION URGENTE</td>
				</tr>';		
		$aux .= '<tr class="success">
					<td>FUNCION PELIGROSA</td>
					<td>Este archivo funciones denominadas como peligrosas o malintencionadas</td>
					<td></td>
				</tr>';				
		$aux .= '<tr class="warning">
					<td>JOOMLA FILE</td>
					<td>Archivo de Core de Joomla que contiene funciones peligrosas o malintencionadas</td>
					<td></td>
				</tr>';	
		$aux .= '<tr>
					<td>HTACCESS FILE</td>
					<td>Archivo potencialmente peligroso si no reconocemos la fecha de actualización.</td>
					<td>PELIGRO REVISION URGENTE</td>
				</tr>';	
		$aux .= '<tr class="info">
					<td>PERMISO DENEGADO</td>
					<td>No se tiene permisos para revisar este archivo.</td>
					<td></td>
				</tr>';	
		$aux .= '</table >';
				
				
		/*  PARAMETROS  */
		$aux .= '<table class="table table-bordered table-hover table-condensed">';
		$aux .= '<tr style="background-color:#AEC6CF;" >
					<th>PARAMETRO</th>
					<th>DESCRIPCION</th>
					<th>VALOR</th>
				</tr>';

		$aux .= '<tr>
					<td>days</td>
					<td>Archivos modificados en los ultimos</td>';		
		$days = 0;
		if(isset($_GET['days'])&&$_GET['days']) {
			$days = (int) $_GET['days'];
			$aux .= '<td>'.$days.' dias</td></tr>';
		}else{
			if(isset($_GET['show'])&&$_GET['show']=='1') {
				$days = 0;
				$aux .= '<td>INFINITO</td></tr>';		
			}elseif(isset($_GET['show'])&&$_GET['show']=='0') {
				$days = 0;
				$aux .= '<td>INFINITO</td></tr>';
			}else{
				$days = 20;
				$aux .= '<td>20 dias</td></tr>';		
			}
		}
		if($days == 0){
			$aux .= '<tr>
						<td>manual</td>
						<td>Muestra los archivos que no se pueden Leer</td>';				
			if(isset($_GET['manual'])&&$_GET['manual']=='0'){
				$aux .= '<td>NO</td></tr>';
			}else{
				$aux .= '<td>SI</td></tr>';	
			}
				
			$aux .= '<tr>
						<td>delete</td>
						<td>Elimina los ficheros configurados en el array del codigo</td>';
			if(isset($_GET['delete'])&&$_GET['delete']=='1') {
				$aux .= '<td>SI</td></tr>';
			}else{
				$aux .= '<td>NO</td></tr>';
			}
			
			$aux .= '<tr>
						<td>commons</td>
						<td>Muestra los archivos del Core de JOOMLA</td>';
			if(isset($_GET['commons'])&&$_GET['commons']=='0') {
				$aux .= '<td>NO</td></tr>';
			}else{
				$aux .= '<td>SI</td></tr>';
			}
		}




		$aux .= '</table >
				<br /><pre><b>Total: '.$ssw.' registros del alto Riesgo</b> (El tiempo de ejecución del Script ha sido de '.$time_dif.' segundos)<br /><br />';	
		$aux .= '<b>Vrs. del Script --> '.$version.'</b><br /></pre>';
			
		$msg = $aux . $msg;
		
		$conformato = "";
		$conformato .= "<div class='container'>";
		$conformato .= "	<div class='table-responsive'>";
		$conformato .= $msg;
		$conformato .= "</div>";
		$conformato .= "</div>";
		$msg = $conformato;
		
		return;
	}
	/**********************************************************************************************/
	/**************************************** FIN FUNCION *****************************************/
	
?>
