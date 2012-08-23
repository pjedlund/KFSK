<?php
/**
 * Baskonfiguration för WordPress.
 *
 * Denna fil innehåller följande konfigurationer: Inställningar för MySQL,
 * Tabellprefix, Säkerhetsnycklar, WordPress-språk, och ABSPATH.
 * Mer information på {@link http://codex.wordpress.org/Editing_wp-config.php 
 * Editing wp-config.php}. MySQL-uppgifter får du från ditt webbhotell.
 *
 * Denna fil används av wp-config.php-genereringsskript under installationen.
 * Du behöver inte använda webbplatsen, du kan kopiera denna fil direkt till
 * "wp-config.php" och fylla i värdena.
 *
 * @package WordPress
 */

// ** MySQL-inställningar - MySQL-uppgifter får du från ditt webbhotell ** //
/** Namnet på databasen du vill använda för WordPress */
define('DB_NAME', 'u5165890_3');

/** MySQL-databasens användarnamn */
define('DB_USER', 'u5165890');

/** MySQL-databasens lösenord */
define('DB_PASSWORD', 'vonnegut#');

/** MySQL-server */
define('DB_HOST', 'mysql.u5165890.fsdata.se');

/** Teckenkodning för tabellerna i databasen. */
define('DB_CHARSET', 'utf8');

/** Kollationeringstyp för databasen. Ändra inte om du är osäker. */
define('DB_COLLATE', '');

/**** Limit postrevisions ****/
define('AUTOSAVE_INTERVAL', 300 ); // seconds
define('WP_POST_REVISIONS', 5 );

/**#@+
 * Unika autentiseringsnycklar och salter.
 *
 * Ändra dessa till unika fraser!
 * Du kan generera nycklar med {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * Du kan när som helst ändra dessa nycklar för att göra aktiva cookies obrukbara, vilket tvingar alla användare att logga in på nytt.
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
 * Tabellprefix för WordPress Databasen.
 *
 * Du kan ha flera installationer i samma databas om du ger varje installation ett unikt
 * prefix. Endast siffror, bokstäver och understreck!
 */
$table_prefix  = 'wp_';

/**
 * WordPress-språk, förinställt för svenska.
 *
 * Du kan ändra detta för att ändra språk för WordPress.  En motsvarande .mo-fil
 * för det valda språket måste finnas i wp-content/languages. Exempel, lägg till
 * sv_SE.mo i wp-content/languages och ange WPLANG till 'sv_SE' för att få sidan
 * på svenska.
 */
define('WPLANG', 'sv_SE');

/** 
 * För utvecklare: WordPress felsökningsläge. 
 * 
 * Ändra detta till true för att aktivera meddelanden under utveckling. 
 * Det är rekommderat att man som tilläggsskapare och temaskapare använder WP_DEBUG 
 * i sin utvecklingsmiljö. 
 */ 
define('WP_DEBUG', false);

/* Det var allt, sluta redigera här! Blogga på. */

/** Absoluta sökväg till WordPress-katalogen. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Anger WordPress-värden och inkluderade filer. */
require_once(ABSPATH . 'wp-settings.php');