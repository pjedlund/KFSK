<?php
/**
 * Baskonfiguration f�r WordPress.
 *
 * Denna fil inneh�ller f�ljande konfigurationer: Inst�llningar f�r MySQL,
 * Tabellprefix, S�kerhetsnycklar, WordPress-spr�k, och ABSPATH.
 * Mer information p� {@link http://codex.wordpress.org/Editing_wp-config.php 
 * Editing wp-config.php}. MySQL-uppgifter f�r du fr�n ditt webbhotell.
 *
 * Denna fil anv�nds av wp-config.php-genereringsskript under installationen.
 * Du beh�ver inte anv�nda webbplatsen, du kan kopiera denna fil direkt till
 * "wp-config.php" och fylla i v�rdena.
 *
 * @package WordPress
 */

// ** MySQL-inst�llningar - MySQL-uppgifter f�r du fr�n ditt webbhotell ** //
/** Namnet p� databasen du vill anv�nda f�r WordPress */
define('DB_NAME', 'u5165890_3');

/** MySQL-databasens anv�ndarnamn */
define('DB_USER', 'u5165890');

/** MySQL-databasens l�senord */
define('DB_PASSWORD', 'vonnegut#');

/** MySQL-server */
define('DB_HOST', 'mysql.u5165890.fsdata.se');

/** Teckenkodning f�r tabellerna i databasen. */
define('DB_CHARSET', 'utf8');

/** Kollationeringstyp f�r databasen. �ndra inte om du �r os�ker. */
define('DB_COLLATE', '');

/**** Limit postrevisions ****/
define('AUTOSAVE_INTERVAL', 300 ); // seconds
define('WP_POST_REVISIONS', 5 );

/**#@+
 * Unika autentiseringsnycklar och salter.
 *
 * �ndra dessa till unika fraser!
 * Du kan generera nycklar med {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * Du kan n�r som helst �ndra dessa nycklar f�r att g�ra aktiva cookies obrukbara, vilket tvingar alla anv�ndare att logga in p� nytt.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '^*t!2u&G!Le}|m!FpOO.{kdMqZ/m56_j2GE[F?ZeT?=&`=hvy!6Hj}]5N5Z7z(0@');
define('SECURE_AUTH_KEY',  'D$?bR~M<Z>+v|xo)*w|b#JmRp<_/P#-]vAhtmF4X|tO!*,GHOd 7AfRw|H+/|uVI');
define('LOGGED_IN_KEY',    ';obm-@?~I mMz,nv5l1om--vs-1fpyIp6o&R|%DkM|wHv+|-%Ax>ukK[}IL-H>^%');
define('NONCE_KEY',        '+0<<2e|msz@m*Y3`<$W+WwHfkBf:S/c)/w&;b-@#*|s:EurR$4B0oTdjx!f~Q~dk');
define('AUTH_SALT',        'CZ-PQQieG(k0,hE^QyJ-[-fO2n?c9Gr4+7%7RwjF#a%Fa88U-D&M}f-.|gpu%ak?');
define('SECURE_AUTH_SALT', 'q(r,uM@t#nhz{!!z?d@q|b0h+dUKy68&y#:!;X=p%j6;Mm,A]#oe|#KIb _6HI9k');
define('LOGGED_IN_SALT',   ')ao%)b<-dONUfFONcu@Bzfwn/YI2/OQ}><MlVTu 2~m~Q=j13?J-jCd3!SmZ!:|[');
define('NONCE_SALT',       'I9Q>}ccoagCU a)A78>)~,!f80)oKHW1W$Fo#JZ^~i8(r(Zy^x/tidE@-ysFx[n!');

/**#@-*/

/**
 * Tabellprefix f�r WordPress Databasen.
 *
 * Du kan ha flera installationer i samma databas om du ger varje installation ett unikt
 * prefix. Endast siffror, bokst�ver och understreck!
 */
$table_prefix  = 'wp_';

/**
 * WordPress-spr�k, f�rinst�llt f�r svenska.
 *
 * Du kan �ndra detta f�r att �ndra spr�k f�r WordPress.  En motsvarande .mo-fil
 * f�r det valda spr�ket m�ste finnas i wp-content/languages. Exempel, l�gg till
 * sv_SE.mo i wp-content/languages och ange WPLANG till 'sv_SE' f�r att f� sidan
 * p� svenska.
 */
define('WPLANG', 'sv_SE');

/** 
 * F�r utvecklare: WordPress fels�kningsl�ge. 
 * 
 * �ndra detta till true f�r att aktivera meddelanden under utveckling. 
 * Det �r rekommderat att man som till�ggsskapare och temaskapare anv�nder WP_DEBUG 
 * i sin utvecklingsmilj�. 
 */ 
define('WP_DEBUG', false);

/* Det var allt, sluta redigera h�r! Blogga p�. */

/** Absoluta s�kv�g till WordPress-katalogen. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Anger WordPress-v�rden och inkluderade filer. */
require_once(ABSPATH . 'wp-settings.php');