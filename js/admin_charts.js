(function($){
 
    //si la variable data n'est pas définie on retourne 
    //false pour stoper l'execution du script
    if (typeof(data) === 'undefined') {
        return false;
    }
 
    //si la variable data_type dans le tableau data est égale à chart_most_popular (non utilisée encore)
    if ( 'chart_most_popular' == data.data_type ) {
 
        var post_titles = [],
            post_comment_count = [];
 
        $( data.post_data ).each(function() {
 
            post_titles.push( this.post_title );
            post_comment_count.push( parseInt( this.comment_count ) );
 
        });
 
        $('#chart-stats').highcharts({
            chart: {
                type: data.chart_type
            },
            title: {
                text: 'Most Popular Posts (by number of comments)'
            },
            xAxis: {
                categories: post_titles
            },
            yAxis: {
                title: {
                    text: 'Number of Comments'
                }
            },
            series: [
                {
                    name: 'Comments Count',
                    data: post_comment_count
                }
            ]
        });
 
    // ou si la variable data_type dans le tableau data est égale à "cammenbert"
    } else if ( 'cammenbert' == data.data_type ) {
 
        var array  = [];
        
        //alors pour chaque élement de post_data est ajouté au tableau ARRAY precedemment déclaré
        $( data.post_data ).each(function() {
            array.push( [ this.name, parseInt( this.count ) ] );
        });
 
        //initialisation de la fonction highcharts (voir highcharts.js)
        $('#chart-stats').highcharts({
            title: {
                //avec comme titre l'élement titre du tableau data
                text: data.titre 
            },
            tooltip: { 
                // création de l'infobulle en survol des secteurs angulaires sur deux lignes 
                // Un -> affichage numérique des valeurs
                // Deux -> affiche le pourcentage des valeurs
                pointFormat: 'nombre de {series.name}: <b>{point.y}</b><br> % de {series.name}: <b>{point.percentage:.1f}%</b>'
            },
            //création de la série de type PIE s'appelant ici des PART avec comme donnée le contenu du tableau ARRAY
            series: [
                {
                    type: 'pie',
                    name: 'part',
                    data: array
                }
            ]
        });
    }
 
 // ceci est du jquery
}(jQuery));