<?php define('WP_CACHE', true);
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

/**#@+
 * Unika autentiseringsnycklar och salter.
 *
 * Ändra dessa till unika fraser!
 * Du kan generera nycklar med {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * Du kan när som helst ändra dessa nycklar för att göra aktiva cookies obrukbara, vilket tvingar alla användare att logga in på nytt.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'LS-2+Lt|QW%0jp{L30_AFn---b?F66|julRR:6sF2(n^8a`}kv!{DDD>0{c,p% L');
define('SECURE_AUTH_KEY',  '7X#.cUA`(R2~QQx;<Ys=4=1dzROv8ux?xkmD}pQ=9D@yBVOY9osD}l0E(Ff`?EDG');
define('LOGGED_IN_KEY',    'sm^<kmo-G,d)|oct3u}B}%S:j1{K@},x%u|*:V; |=.!/Y=kmruRj0.,GYvrrhj(');
define('NONCE_KEY',        'j>`,>ohey1[#V*,3N{|p2D48S6-4}fpmMVmaQv-J|N.IJ+E{tOPKEK`J[6J/<x$-');
define('AUTH_SALT',        '@c{K#s>^mGw]=M;.5xUf,M>G e}RCK|usJO>pF%v,5Pl-*z7Mr1|iAI%Xuhf/<6b');
define('SECURE_AUTH_SALT', 'Zs-W1UA|P&7Z+q]EF8cgUtukiVLGJX-9Y*[L!yIw;-}{S.{<r>*+CF1Zn.h1;)XQ');
define('LOGGED_IN_SALT',   '[nX@w!^jj11Tb9#Oat]?`FkI=&4F8X`(,z)91^^!n:ksM^Hq?4.(xuYdBEh;{(rV');
define('NONCE_SALT',       'p+/tT?A%AH FH86J~olPCFz3ci@s:%v5Uhm+-0Bn98#`W(To7[hMl46$$ c*PDVu');

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