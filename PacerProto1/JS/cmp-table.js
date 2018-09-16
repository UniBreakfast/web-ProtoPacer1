// var tableHTML = TblGen.table(DataGen.headerows(), 'my-table');
var tableHTML = TblGen.table(DataGen.headerows(0, 8, 16), 'my-table');
var cont_tableHTML = '<div class=table-grid>'+
    '<div class=top-headers>'+tableHTML+'</div>'+
    '<div class=data-rows>'+tableHTML+'</div>'+
    '<div class=bottom-headers>'+tableHTML+'</div>'+
    '</div>';
document.querySelector('.cont-table').innerHTML = cont_tableHTML;
