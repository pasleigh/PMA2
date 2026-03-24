/**
 * Created by cenpas on 02/02/2017.
 */
//Highcharts.chart('ContainerTeachingHoursChart',

var my_projects_chart =    {
    chart: {
        type: 'bar'
    },
    title: {
        text: 'Project Supervision for all project modules'
    },
    subtitle: {
        text: 'Comparitive Overview of Project Supervision <BR>(bar lengths include a 10% weight for second marking)'
    },
    xAxis: {
        categories: ['Andy', 'Cath', 'Duncan', 'Basheer', 'David'],
        title: {
            text: null
        }
    },
    // Highcharts 2.0 default colours
    colors: [
        '#4572A7', '#AA4643', '#89A54E', '#80699B', '#3D96AE',
        '#DB843D', '#92A8CD', '#A47D7C', '#B5CA92'
//        teaching_col, pgr_col, admin_col, uni_admin_col, comm_service_col, external_col, academic_col,
    ],
    yAxis: {
        min: 0,
        title: {
            text: 'Projects',
            align: 'high'
        },
        labels: {
            overflow: 'justify'
        }
    },
    tooltip: {
        valueSuffix: ' credits',
        useHTML: true,
        //headerFormat: '<em>Date: {point.key}</em><br/>',
        formatter: function(){
            var text;
            text = '<em>' + this.x + '</em><br/><b>' +this.series.name +  '</b><br />Supervised: <b>' + this.point.super + '</b>';
            text += '<br />Second marked: <b>' + this.point.second + '</b>';
            return  text;
        }
    },
    plotOptions: {
        /*
        bar: {
            dataLabels: {
                enabled: true,
                style: {fontWeight: 'normal'}
            }
        },
        */
        series: {
            stacking: 'normal'
        }
    },
    legend: {
        layout: 'vertical',
        align: 'right',
        verticalAlign: 'top',
        x: -40,
        y: 80,
        floating: true,
        borderWidth: 1,
        backgroundColor: ((Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF'),
        shadow: false,
        style: {fontWeight: 'normal'},
        enabled: false
    },
    credits: {
        enabled: false
    },
    series: [{
        name: 'Projects Supervised',
        data: [107, 31, 635, 203, 2]
    }
    ],
    exporting: {
        filename: "staff_load_chart"
        /*,
        menuItemDefinitions: {
            // Custom definition
            label: {
                onclick: function () {
                    this.renderer.label(
                        'You just clicked a custom menu item',
                        100,
                        100
                        )
                        .attr({
                            fill: '#a4edba',
                            r: 5,
                            padding: 10,
                            zIndex: 10
                        })
                        .css({
                            fontSize: '1.5em'
                        })
                        .add();
                },
                text: 'Show label'
            }
        },
        buttons: {
            contextButton: {
                menuItems: ['downloadPNG', 'downloadSVG', 'separator', 'label']
            }
        }*/
    }
};
//);