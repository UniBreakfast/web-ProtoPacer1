const DataGen = (()=>{
  function headerows(id, col_num, rec_num){
    const headers = [], rows = [];
    switch (id){
      case 1:
        var cols = 5;
        var recs = 25;
        for (let i=0;i<cols;i++) headers.push(StrGen.word(14, true));
        for (let j=0;j<recs;j++) {
          let row = [];
          for (let i=0;i<cols;i++) {
            if (!i) row.push(j+1);
            else if (i==1) row.push(StrGen.Word());
            else if (i==2) row.push(StrGen.number(3));
            else if (i%2) row.push(StrGen.word());
            else row.push(StrGen.number0());
          }
          rows.push(row);
        }
        break;
      case 2:
        break;
      case 3:
        break;
      case 0:
        if (col_num!=undefined) var cols = col_num;
        if (rec_num!=undefined) var recs = rec_num;
      default:
        if (cols==undefined) var cols = StrGen.rnum(5)+2;
        if (recs==undefined) var recs = StrGen.rnum(9)+4;
        for (let i=0;i<cols;i++) headers.push(StrGen.word());
        for (let j=0;j<recs;j++) {
          let row = [];
          for (let i=0;i<cols;i++) {
            if (!i) row.push(j+1);
            else if (!Boolean((i+1)%6)) row.push('"'+StrGen.Phrase()+'"');
            else if (i==1) row.push(StrGen.Word());
            else if (i==2) row.push(StrGen.number(3));
            else if (i%2) row.push(StrGen.word());
            else row.push(StrGen.number0());
          }
          rows.push(row);
        }
    }
    return {headers, rows}
  }

  return {headerows}
})();

const TblGen = (()=>{
  function table(data_obj, table_classes){
    const headers = data_obj.headers, rows = data_obj.rows,
          headersHTML = "<tr>" +
            headers.map(header => "<th>" + header + "</th>")
              .join("") + "</tr>",
          rowsHTML = rows.map(row => "<tr>" +
            row.map(cell => "<td>" + cell + "</td>").join("") + "</tr>")
              .join(""),
          tableHTML = "<table" +
            (table_classes?' class="'+table_classes+'"':"") + ">" +
              headersHTML+rowsHTML + "</table>";
    return tableHTML
  }

  function table_headers(data_obj, table_classes){
    const headers = data_obj.headers,
          headersHTML = "<tr>" +
            headers.map(header => "<th>" + header + "</th>")
              .join("") + "</tr>",
          tableHTML = "<table" +
            (table_classes?' class="'+table_classes+'"':"") + ">" +
              headersHTML + "</table>";
    return tableHTML
  }

  function grid_table(data_obj, grid_table_classes){
    const headers = data_obj.headers, rows = data_obj.rows,
          headersHTML = "<tr>" +
            headers.map(header => "<th>" + header + "</th>")
              .join("") + "</tr>",
          rowsHTML = rows.map(row => "<tr>" +
            row.map(cell => "<td>" + cell + "</td>").join("") + "</tr>")
              .join(""),
          tableHTML = "<table" +
            (grid_table_classes?' class="'+grid_table_classes+'"':"") + ">" +
              headersHTML+rowsHTML + "</table>";
    return tableHTML
  }

  return {table, table_headers, grid_table}
})();
