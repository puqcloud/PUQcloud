// Sparklines


$(document).ready(() => {

    $(function () {

        $('.bar-sparkline').sparkline([[4, 8], [2, 7], [2, 6], [2, 7], [3, 5], [2, 7], [2, 6], [2, 7], [3, 5], [4, 7], [2, 5], [3, 5], [4, 7], [4, 7], [5, 7], [4, 8], [2, 7], [2, 6], [2, 7], [3, 5]], {
            type: 'bar',
            height: '35px',
            barWidth: '5px',
            barSpacing: '2px'
        });

        $('.bar-sparkline-2').sparkline('html', { type: 'bar', barColor: '#0099ff', height: '35px', barWidth: '5px', barSpacing: '2px' });

        var myvalues = [10, 8, 5, 7, 4, 4, 1];
        $('.dynamic-sparkline').sparkline(myvalues, { height: '35px', width: '135px' });

        $('.tristate-sparkline').sparkline('html', { type: 'tristate', barColor: 'var(--primary)', height: '35px', barWidth: '5px', barSpacing: '2px' });

        $('.discrete-sparkline').sparkline('html', { type: 'discrete', barColor: 'var(--primary)', height: '45px', barSpacing: '4px' });

        $('.pie-sparkline').sparkline('html', { type: 'pie', barColor: 'var(--primary)', height: '45px', width: '45px' });

    });

});