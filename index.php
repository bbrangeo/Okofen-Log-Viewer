<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Oköfen</title>

	<script type="text/javascript" src="//code.jquery.com/jquery-1.9.1.js"></script>

<script type='text/javascript'>//<![CDATA[ 

$(function () {


    var seriesOptions = [],
        seriesCounter = 0,
        okofenSeries = [{name: 'TExt', type: 'temp', pane: 0, type: 'line', pointInterval: 1},
												{name: 'TInt', type: 'temp', pane: 0, type: 'line', pointInterval: 1}, 
												{name: 'TChaudiere', type: 'chaudiere', pane: 1, type: 'line', pointInterval: 1},
												{name: 'TDepart', type: 'chaudiere', pane: 1, type: 'line', pointInterval: 1},
												{name: 'ECS1 T demarrage', type: 'chaudiere', pane: 1, type: 'line', pointInterval: 1},
												{name: 'PE1 T flamme', type: 'chaudiere', pane: 1, type: 'line', pointInterval: 1},
												{name: 'Pellets', type: 'Pellets', pane: 2, type: 'column', pointInterval: 24 * 3600 * 1000}
												],
        // create the chart when all data is loaded
        createChart = function () {

            $('#container').highcharts('StockChart', {
                yAxis: [{
										labels: {
												align: 'right',
												x: -3
										},
										title: {
												text: 'Températures (°C)'
										},
										height: '40%',
										lineWidth: 2
								}, {
										labels: {
												align: 'right',
												x: -3
										},
										title: {
												text: 'Temperature Chaudière (°C)'
										},
										top: '45%',
										height: '20%',
										offset: 0,
										lineWidth: 2,
										floor: 0
           			}, {
										labels: {
												align: 'right',
												x: -3
										},
										title: {
												text: 'Pellets (kg)'
										},
										top: '70%',
										height: '25%',
										offset: 0,
										lineWidth: 2,
										floor: 0
           			}],

                plotOptions: {
										column: {
												pointInterval: 24 * 3600 * 1000
										}
           			},

                tooltip: {
                    pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>{point.y}</b><br/>',
                    valueDecimals: 2
                },

                series: seriesOptions
            });
        };

    $.each(okofenSeries, function (i, aSeries) {

        $.getJSON('callback.php?value=' + aSeries.name + '&callback=?',    function (data) {

            seriesOptions[i] = {
                name: aSeries.name,
                data: data,
                yAxis: aSeries.pane,
                type: aSeries.type,
                pointInterval: aSeries.pointInterval
            };

            // As we're loading the data asynchronously, we don't know what order it will arrive. So
            // we keep a counter and create the chart when all the data is loaded.
            seriesCounter += 1;

            if (seriesCounter === okofenSeries.length) {
                createChart();
            }
        });
    });
});
//]]>  

</script>

<script src="http://code.highcharts.com/stock/highstock.js"></script>
<script src="http://code.highcharts.com/stock/modules/exporting.js"></script>

</head>

<body>

	<div id="container" style="height: 700px; min-width: 310px"></div>
</body>

</html>
