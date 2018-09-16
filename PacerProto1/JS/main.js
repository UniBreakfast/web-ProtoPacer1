(()=>{
  var btn_sb = document.querySelector('.btn-sidebar'),
      cont_sb = document.querySelector('.cont-sidebar').style;
  btn_sb.onclick = ()=>{
    cont_sb.display = 'block';
    setTimeout(()=>document.onclick = (e)=>{
      if(e.target.className != 'cont-sidebar') {
        cont_sb.display = null;
        document.onclick = null;
      }
    }, 1);
  }
})();
