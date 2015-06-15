# undo-hacking
<pre>
ES-ES
undo_hacking.php - Control del Archivos Web
NOTE: Requiere PHP versión 5 o superior
@package ---
@author Antonio Ursúa Bayona
@website http://aursua.blogspot.com.es/
@GitHub https://github.com/antonioursuab/undo-hacking.git
@Wiki https://github.com/antonioursuab/undo-hacking/wiki/
@Created 2013
@version 1.0.0 Código Base
         1.1.0 Se añade las funciones de envío de correo y se recoge el HTML para enviarlo por correo
               Se añaden los archivos PHPmailes y Smtp
         1.2.0 Se añade un control de directorio para no tener que escribir el directorio en el que esta.
               En el asunto del correo se carga la variable HTTP_HOST
         1.3.0 Se añade el parámetro Show, que tiene dos modos 1 saca por pantalla la info y 0 o null, lo envía al correo.
         1.4.0 Se configuran las leyendas
         1.5.0 Se añade el nombre de este archivo para que no lo pille como fichero modificado recientemente "undo_hacking.php". Line 121
         1.6.0 Se añade el parametro delete_htaccess, que si es 1 elimina los archivos htaccess del directorio.
         1.7.0 Se han solucionado algún defecto de estilo. Y se añade al resultado el ID de Versión, para tener más control.
               Se quita la opcion de si muestra o envia el correo, ya que lo enviara siempre pero cambiando el Asunto por OK, en el caso de que no haya ningun archivo.
               Se mete el Archivo HTACCESS al control por dias en vez de dejarlo que simepre saldria como malo.
         1.8.0 Se añaden a la lista de extensiones ("gif", "jpg", "JPG"). Se añaden como funciones peligrosas "exif_read_data", "file_get_contents"
         1.9.0 Se añaden estilos con Bootstrap a los resultados que se muestran en pantalla y se devuelven por correo
 
 
</pre>
