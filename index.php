<?php
/*
Plugin Name: Outils Admin pour voyagezfute.com
Plugin URI: 
Description: outils de stats et d'affichage des demandes clients
Version: beta
Author: laurent HAZARD
Author URI: http://laurent-cv.llovem.eu/
License: 
*/
 
// Plugin root directory
define( 'ROOT', plugin_dir_url( __FILE__ ) );
 
/**
 * intégration du plugin dans l'admin WP
 * @return [array] élement d'intégration dans WordPress 
 */
function admin_page() {
    add_menu_page( 'Gestions Menu', 'Gestion', 'manage_options', 'demande-menu', 'charts_page', plugins_url( '/outils_voyagerfute/images/icon.png' ));
    add_submenu_page( 'demande-menu', 'Les demandes', 'Les demandes', 'manage_options', 'les-demandes', 'les_demandes' );
    
    if($_GET['page'] == 'demande'){
       add_submenu_page( 'demande-menu', 'Demande', 'Demande', 'manage_options', 'demande', 'demande' ); 
    }
}
 

// place la fonction admin_menu dans l'administration
add_action( 'admin_menu', 'admin_page' );

// place les css dans le header html
add_action('admin_head', 'styles');

/**
 * verifie la concordance du post et de la variable envoyée depuis le
 * select de la page 'charts_page' pour l'envoyer 
 * à la fonction 'chart_add_scripts' qui génère le graphique 
 * @param  [string]     $option [string]
 * @return [string]     graphique demandé   
 */
function selected_option( $option ) {
    if ( $option == $_POST['chart_data_type'] ) {
        echo 'selected="selected"';
    }
}
 
/**
 * contenu de la page gestion avec un formulaire qui envoie en post les demandes d'affichage du graphique
 * @return [string] html
 */
function charts_page() {

    ?>
    <div class="wrap">
        <h2>Statistiques</h2>
        <form action="" method="post" name="form_chart" id="form_chart">
            <p>
                <label for="chart_data_type">Que sois tu afficher ?</label>
                <select name="chart_data_type" id="chart_data_type">
                    <option value="statCivility" <?php selected_option( 'statCivility' ); ?>>Type civilité demandeur</option>
                    <option value="destinations" <?php selected_option( 'destinations' ); ?>>Les destinations demandé</option>
                </select>
            </p>
            <input type="submit" class="button-primary" value="Afficher !" id="show_chart" name="show_chart" />
        </form>
        <div id="chart-stats" class="chart-container" style="width:900px; height:500px;"></div>
    </div>
    <?php
}
 
/**
 * charge les elements JS et css pour générer un graphique
 * @return [string] html
 */
function chart_register_scripts() {
    wp_register_script(
        'highCharts',
        ROOT . 'js/highcharts.js',
        array( 'jquery' ), 
        '3.0.7',
        true 
    );
    wp_register_script(
        'adminCharts',
        ROOT . 'js/admin_charts.js',
        array( 'highCharts' ),
        '1.0',
        true
    );
}
 
add_action( 'admin_enqueue_scripts', 'chart_register_scripts' );
 
/**
 * [chart_add_scripts description]
 * @param  [type] $hook [description]
 * @return [type]       [description]
 */
function chart_add_scripts( $hook ) {

    // variable globale pour effectuer un requète personnalisée mysql
    global $wpdb;   

    //si nous sommes bien dans la bonne page ici  'toplevel_page_demande-menu'
    if ( 'toplevel_page_demande-menu' == $hook ) {

        wp_enqueue_style( 'adminChartsStyles' );
        wp_enqueue_script( 'adminCharts' );
 
        // si POST n'est pas vide
        if ( isset( $_POST['show_chart'] ) ) {
 
            //si le graphique demandé est celui de 'statCivility'
            if ( 'statCivility' == $_POST['chart_data_type'] ) {
 
                //requete comptant les differentes civility qui sont regroupées afin de mettre en evidence les différents types de civility
                $statCivility = $wpdb->get_results("SELECT count(civility) AS count, civility AS name FROM users group by civility");

                //tableau d'options qui sera envoyé à 'highcharts.js' pour générer un graphique
                $data = array(
                    'data_type'  => 'cammenbert',
                    'titre'      => 'proportion homme/femme',
                    'chart_type' => 'pie',
                    'post_data'  => $statCivility
                );
                
                //envoie '$data' vers adminCharts
                wp_localize_script( 'adminCharts', 'data', $data );
 
            }
 
            //si le graphique demandé est celui de 'destinations'
            if ( 'destinations' == $_POST['chart_data_type'] ) {
 
                $categories = $wpdb->get_results("SELECT COUNT(destinationCity) as count, destinationCity AS name  FROM travels group by name order by count DESC limit 0 ,10");
                $data = array(
                    'data_type'  => 'cammenbert',
                    'titre'      => 'Top 10 des destinations les plus demandé',
                    'chart_type' => 'pie',
                    'post_data'  => $categories
                );
 
                wp_localize_script( 'adminCharts', 'data', $data );
 
            }
 
        }
 
    }
}
 
add_action( 'admin_enqueue_scripts', 'chart_add_scripts' );

/**
 * chargement du CSS dans le plugin
 * @return [string] css general du plugin
 */
function styles() {
    echo '<link rel="stylesheet" href="'.ROOT.'/css/default.css" type="text/css" media="all" />';
}


/**
 * page de demande avec l'historique des clients
 * ainsi que la demande avec toutes les informations 
 */
function demande(){

    // nécessaire pour faire des requètes. 
    // cette variable globale contient les informations de connexion à la base de données
    global $wpdb;

    //définit la variable $Order
    if(isset($_GET['sort'])){

        $Order = $_GET['sort'];

    } else{

        $Order = "travelCommandTime";
    }

    // requète mysql affichant le voyage dont l'id est recu en get
    $demande = $wpdb->get_results( 'SELECT *,
                                        U.user_id,
                                        U.civility,
                                        U.lastname,
                                        U.surname,
                                        U.phone,
                                        T.id,
                                        DATE_FORMAT(T.travelCommandTime,"%d/%m/%Y %H:%i") AS travelCommandTime, -- formatage de la date
                                        T.budget,
                                        T.departureCity1,
                                        T.destinationCity,
                                        T.paypal_status
                                    FROM
                                        users AS U
                                            LEFT JOIN
                                        travels AS T ON U.user_id = T.user_id
                                    WHERE T.id = "'.$_GET['id'].'" ');

    //requète affichant TOUTES les demandes du client dont l'user_id a été recuperé par la requète du dessus
    $demandeMemeUser = $wpdb->get_results( 'SELECT 
                                        U.user_id,
                                        U.civility,
                                        U.lastname,
                                        U.surname,
                                        U.phone,
                                        T.id,
                                        DATE_FORMAT(T.travelCommandTime,"%d/%m/%Y %H:%i") AS travelCommandTime, -- formatage de la date
                                        T.budget,
                                        T.departureCity1,
                                        T.destinationCity,
                                        T.paypal_status
                                    FROM
                                        users AS U
                                            LEFT JOIN
                                        travels AS T ON U.user_id = T.user_id
                                    WHERE U.user_id = "'.$demande[0]->user_id.'"
                                    ORDER BY "'.$Order.'" "'.$_GET['sens'].'" ');


    echo '<div class="wrap"><h2>Demande de : '.$demande[0]->civility.' '.$demande[0]->lastname.' '.$demande[0]->surname.'</h2></div>';

    echo '<h3>Historique ('.count($demandeMemeUser).')</h3>';

?>
    <!-- première ligne du tableau affichant l'historique des demandes -->
    <table class="widefat">
    <thead>
        <tr>
            <th>Action</th>
            <th>Nom / Prenom</th>
            <th>Téléphone</th>
            <!-- 
                construction du lien pour trier les colonnes 
                $id : id du voyage
                $sort : nom de la colonne à trier
                $Order : sens du tri
            -->
            <th><a href="admin.php?page=demande&id=<?php echo $_GET['id']; ?>&sort=travelCommandTime&sens=<?php 
            echo ( $Order == "travelCommandTime" ) ? "DESC" : "ASC"; ?>">Date demande</a></th>
            <th><a href="admin.php?page=demande&id=<?php echo $_GET['id']; ?>&sort=budget&sens=<?php 
            echo ( $Order == "budget" ) ? "DESC" : "ASC"; ?>">budget</a></th>
            <th><a href="admin.php?page=demande&id=<?php echo $_GET['id']; ?>&sort=departureCity1&sens=<?php 
            echo ( $Order == "departureCity1" ) ? "DESC" : "ASC"; ?>">Ville de depart</a></th>
            <th><a href="admin.php?page=demande&id=<?php echo $_GET['id']; ?>&sort=destinationCity&sens=<?php 
            echo ( $Order == "destinationCity" ) ? "DESC" : "ASC"; ?>">Destination</a></th>
            <th><a href="admin.php?page=demande&id=<?php echo $_GET['id']; ?>&sort=paypal_status&sens=<?php 
            echo ( $Order == "paypal_status" ) ? "DESC" : "ASC"; ?>">paypal status</a></th>
        </tr>
    </thead>
    <tbody>

    <!-- boucle affichant pour chaque ligne une demande -->
    <?php foreach ($demandeMemeUser as $v) {?>

    <!-- alterne la class CSS pour faciliter la lecture 
    en changeant de couleur une ligne sur deux -->
    <?php $alternate = ( $i % 2 ) ? "alternate" : "null"; $i++?>

        <!-- ligne du tableau -->
        <tr class="<?php echo $alternate; ?>">

            <!-- première celulle contenant le lien vers la page qui affiche les informations d'une demande dont l'id est envoyé en get -->
            <td>
                <?php echo '<a href="admin.php?page=demande&id='.$v->id.'">voir</a>'; ?>        
            </td>

            <td> <!-- celulle suivante affichant les objets surname et lastname decodés en utf8 car toutes les informations dans la db ne le sont pas -->
                <?php echo utf8_decode($v->surname); ?> <?php echo utf8_decode($v->lastname); ?>    
            </td>

            <td>        
                <?php echo utf8_decode($v->phone); ?>   
            </td>

            <td>        
                <?php echo utf8_decode($v->travelCommandTime); ?>       
            </td>

            <td>        
                <?php echo $v->budget; ?> €     
            </td>

            <td>        
                <?php echo utf8_decode($v->departureCity1); ?>      
            </td>

            <td>
                <?php echo utf8_decode(html_entity_decode($v->destinationCity)); ?>     
            </td>

            <td>
                <p class="<?php echo $v->paypal_status; ?>"><?php echo $v->paypal_status; ?><p> 
            </td>

        </tr>

    <!-- fin de la boucle foreach  -->
    <?php } ?>

    </tbody>
    </table>

    <!-- affiche les informations de la demande selectionnée -->
    <?php

    // si différent de mr le client sera cliente
    if($demande[0]->civility != 'mr') {$e = "e";}
    echo '<h3>Informations client'.$e.'</h3>'; ?>

    <strong>Prénom</strong> : <?php echo $demande[0]->lastname; ?><br>
    <strong>Nom</strong> : <?php echo $demande[0]->surname; ?><br>
    <strong>Age</strong> : <?php echo $demande[0]->age; ?><br>
    <strong>Mail</strong> : <?php echo $demande[0]->email; ?><br>
    <strong>Téléphone</strong> : <?php echo $demande[0]->phone; ?><br>

    <h3>Informations sur la demande</h3>
    Cette demande de voyage est pour 
    <strong><?php echo $demande[0]->groupSize; ?></strong>
    de type   
    <strong><?php echo $demande[0]->groupType; ?></strong>
    pour 
    <strong><?php echo $demande[0]->stayDuration; ?> <?php echo $demande[0]->stayType; ?></strong>
    et pour destination 
    <strong><?php echo $demande[0]->destinationCity; ?> </strong>
    au depart de 
    <strong><?php echo $demande[0]->departureCity1; ?></strong>
    
    <h3>Texte accompagnant la demande</h3>
    <?php echo $demande[0]->customText; ?>
    
<?php  

}


/**
 * page affichant toute les demandes recues sur le site
 */
function les_demandes(){

   global $wpdb;

   // option de tri pour la requète (pourrait etre affectée en JS)
    if(isset($_GET['sort'])){

        $Order = $_GET['sort'];

    } else{

        $Order = "travelCommandTime";
    }

    //requète pour afficher quelques informations sur les demandes
    $demandes = $wpdb->get_results( "SELECT 
    U.user_id,
    U.civility,
    U.lastname,
    U.surname,
    U.phone,
    DATE_FORMAT(T.travelCommandTime,'%d/%m/%Y %H:%i') AS travelCommandTime, -- formatage de la date
    T.budget,
    T.departureCity1,
    T.departureCity2,
    T.destinationCity,
    T.paypal_status,
    T.id
    FROM
        users AS U
            LEFT JOIN
        travels AS T ON U.user_id = T.user_id
    ORDER BY ".$Order." ".$_GET['sens']."" );

    //affiche le nombre de demandes totales dans la base
    echo '<div class="wrap"><h2 class="stabilo">Demandes ('.count($demandes).')</h2>';

?>

<table class="widefat">
<thead>
    <tr>
        <th>Action</th>
        <th><a href="admin.php?page=les-demandes&sort=lastname&sens=<?php 
        echo ( $Order == "lastname" ) ? "DESC" : "ASC"; ?>">Nom / Prenom</a></th>
        <th><a href="admin.php?page=les-demandes&sort=phone&sens=<?php
         echo ( $Order == "phone" ) ? "DESC" : "ASC"; ?>">Téléphone</a></th>
        <th><a href="admin.php?page=les-demandes&sort=travelCommandTime&sens=<?php 
        echo ( $Order == "travelCommandTime" ) ? "DESC" : "ASC"; ?>">Date demande</a></th>
        <th><a href="admin.php?page=les-demandes&sort=budget&sens=<?php 
        echo ( $Order == "budget" ) ? "DESC" : "ASC"; ?>">budget</a></th>
        <th><a href="admin.php?page=les-demandes&sort=departureCity1&sens=<?php 
        echo ( $Order == "departureCity1" ) ? "DESC" : "ASC"; ?>">Ville de depart</a></th>
        <th><a href="admin.php?page=les-demandes&sort=destinationCity&sens=<?php 
        echo ( $Order == "destinationCity" ) ? "DESC" : "ASC"; ?>">Destination</a></th>
        <th><a href="admin.php?page=les-demandes&sort=paypal_status&sens=<?php 
        echo ( $Order == "paypal_status" ) ? "DESC" : "ASC"; ?>">paypal status</a></th>
    </tr>
</thead>
<tbody>



<?php foreach ($demandes as $v) {?>
<?php $alternate = ( $i % 2 ) ? "alternate" : "null"; $i++?>

    <tr class="<?php echo $alternate; ?>">

        <td>
            <!-- envoie de l'id de la demande en GET  -->
            <?php echo '<a href="admin.php?page=demande&id='.$v->id.'">voir</a>'; ?>        
        </td>

        <td> 
            <!-- celulle suivante affichant les objets surname et lastname decodés en utf8 car toutes les informations dans la db ne le sont pas -->        
            <?php echo utf8_decode($v->surname); ?> <?php echo utf8_decode($v->lastname); ?>    
        </td>

        <td>        
            <?php echo utf8_decode($v->phone); ?>   
        </td>

        <td>        
            <?php echo utf8_decode($v->travelCommandTime); ?>       
        </td>

        <td>        
            <?php echo $v->budget; ?> €     
        </td>

        <td>        
            <?php echo utf8_decode($v->departureCity1); ?>      
        </td>

        <td>
            <?php echo utf8_decode(html_entity_decode($v->destinationCity)); ?>     
        </td>

        <td>
            <p class="<?php echo $v->paypal_status; ?>"><?php echo $v->paypal_status; ?><p> 
        </td>

    </tr>

<?php } ?>

</tbody>
</table>
<?  

    echo '</div>';
}