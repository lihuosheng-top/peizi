$(function () {
	
	var b=new Array;
  	var a=$("tbody").children("tr").children("td:nth-child(4)");
	for(var i = 0; i < a.length; i++){
		b.push(a[i].innerText);
	}
for(var j=0;j<b.length;j++){
	if(b[j]==1){
		a[j].innerText="收入";
	}
	else{
		a[j].innerText="支出";
	}
}

});