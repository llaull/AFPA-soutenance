(function($){
 
    //si la variable data n'est pas défini on retourne 
    //false pour stoper l'execution du script
    if (typeof(data) === 'undefined') {
        return false;
    }
 
    //si la variable data_type dans le table data est égale a chart_most_popular (non utiliser encore)
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

    // ou si la variable data_type dans le table data est égale a chart_top_cat (non utiliser encore)
    } else if ( 'chart_top_cat' == data.data_type ) {
 
        var cat_titles = [],
            cat_count = [];
 
        $( data.post_data ).each(function() {
 
            cat_titles.push( this.name );
            cat_count.push( parseInt( this.count ) );
 
        });
 
        $('#chart-stats').highcharts({
            chart: {
                type: data.chart_type
            },
            title: {
                text: 'Top 5 Categories by Posts'
            },
            xAxis: {
                categories: cat_titles
            },
            yAxis: {
                title: {
                    text: 'Number of Posts'
                },
                tickInterval: 5
            },
            series: [
                {
                    name: 'Post Count',
                    data: cat_count
                }
            ]
        });
 
    // ou si la variable data_type dans le table data est égale a "cammenbert"
    } else if ( 'cammenbert' == data.data_type ) {
 
        var array  = [];
        
        //alors pour chaque element de post_data est ajouté au tableau ARRAY precedament déclarer
        $( data.post_data ).each(function() {
            array.push( [ this.name, parseInt( this.count ) ] );
        });
 
        //initialisation de la fonction highcharts (voir highcharts.js)
        $('#chart-stats').highcharts({
            title: {
                //avec comme titre l'element titre du tableau data
                text: data.titre 
            },
            tooltip: { 
                // creation de l'infobulle en survole des secteurs angulaires sur deux lignes 
                // Un -> affiche numerique des valeurs
                // Deux -> affiche le pourcentage des valeurs
                pointFormat: 'nombre de {series.name}: <b>{point.y}</b><br> % de {series.name}: <b>{point.percentage:.1f}%</b>'
            },
            //creation de la serie de type PIE s'appelant ici des PART avec comme donnée le contenu du tableau ARRAY
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