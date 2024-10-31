jQuery(document).ready(function(jQuery){
	/*rmc_initTextarea();
	rmc_initDisplayPrice();
	rmc_initDisplayDates();
	rmc_initDisplayDatetimes();
	rmc_initCheckInputNumber();*/
	rmc_selectTab();
});


function rmc_show_tab(idTab,me,forceRefresh=true){
	/*if (forceRefresh){
		if (rmc_checkChanges('')){
			document.location.href = me.href;
			document.location.reload(true);
		}
		return;
	}*/
	rmc_tabs_section = document.getElementById("rmc_tabs_" + idTab.split("-")[0]);
	rmc_tabs_section.querySelector(".rmc_tabs_active").classList.remove("rmc_tabs_active");
	rmc_tabs_section.querySelector(".rmc_tabs_content_active").classList.remove("rmc_tabs_content_active");
	me.classList.add("rmc_tabs_active");
	document.getElementById(idTab).classList.add("rmc_tabs_content_active");
	return false;
}

function rmc_selectTab(e){
	var url = document.location.href;
	url = url.split("#");
	if (url[1]){
		var anchor = url[1];
		var a = document.querySelector('a[href="#' + anchor + '"');
		var onclick = a.getAttribute("onclick");
		var param1 = onclick.split("'")[1];
		rmc_show_tab(param1,a,false);
	}
	if (document.location.href.indexOf("&tab=") > -1){
		var tabUrl = document.location.href.split("#");
		var anchor = "";
		var endUrl = document.location.href.length;
		if (tabUrl[1]){
			anchor = tabUrl[1];
			endUrl = document.location.href.indexOf("#") - 1;
		}
		var newUrl = document.location.href.substr(0, document.location.href.indexOf("&tab="));
		if (anchor != ""){
			newUrl += "#" + anchor;
		}else{
			newUrl += "#" + document.location.href.substr(document.location.href.indexOf("&tab=") + 5, endUrl);
		}
		history.pushState(null, '', newUrl);  
		
	}
}

function rmc_closeAdminNotice(elt){
	elt.closest("div[class*='rmc_adminNotice']").style.display = "none";
}

function rmc_initListenerTips(idTab){
	tips = document.getElementById("rmc_tabs_" + idTab).getElementsByClassName("dashicons-editor-help");
	for(var i = 0; i < tips.length; i++){
		tips[i].addEventListener("mouseover",function(){document.getElementById("rmc_tabs_tips_" + idTab).innerHTML = this.getAttribute("rmc_title");});
	}
	tabLabels = document.getElementById("rmc_tabs_" + idTab).getElementsByClassName("rmc_tabLabel");
	for(var i = 0; i < tabLabels.length; i++){
		tabLabels[i].addEventListener("mouseover",function(){document.getElementById("rmc_tabs_tips_" + idTab).innerHTML = this.getAttribute("rmc_title");});
	}
	
	tipOnFocus = document.getElementById("rmc_tabs_" + idTab).getElementsByClassName("rmc_tipOnFocus");
	for(var i = 0; i < tipOnFocus.length; i++){
		
		tipOnFocus[i].addEventListener("focus",function(){
			if (this.closest(".rmc_fieldsContent")){
				if (this.closest(".rmc_fieldsContent").querySelector(".dashicons-editor-help")){
					document.getElementById("rmc_tabs_tips_" + idTab).innerHTML = this.closest(".rmc_fieldsContent").querySelector(".dashicons-editor-help").getAttribute("rmc_title");
				}
			}
		});

	}
	rmc_initLiDisplay(idTab);
}

function rmc_initLiDisplay(idTab){
	uls = document.getElementById("rmc_tabs_" + idTab).getElementsByClassName("rmc-sortable-display");
	for(var u = 0; u < uls.length; u++){
		li = uls[u].getElementsByTagName("li");
		for(var i = 0; i < li.length; i++){
			li[i].addEventListener("mouseover",function(){document.getElementById("rmc_tabs_tips_" + idTab).innerHTML = this.getAttribute("rmc_title");});
			li[i].querySelector("span > i.dashicons").addEventListener("click",function(){this.classList.toggle("dashicons-visibility");this.classList.toggle("dashicons-hidden");});
			li[i].querySelector("div > .rmc_liDisplayRequired").addEventListener("click",function(){
				if (this.innerHTML.indexOf("rmc_asterisk") != -1){
					if (this.innerHTML.indexOf("rmc_notrequired") == -1){
						this.innerHTML = '<span class="rmc_asterisk rmc_notrequired">*</span>';
					}else{
						this.innerHTML = '<span class="rmc_asterisk">*</span>';
					}
				}
			});
				
		}
	}
}

function rmc_addField(){
	if (document.getElementById("rmc_rmc_addFieldName").value != ""){
		document.getElementById("rmc_formAddField").submit();	
	}
}

function rmc_deleteField(id){
	document.getElementById("IddeleteField").value = id;
	document.getElementById("rmc_formDeleteField").submit();	
}

function rmc_updateParameters(idTab){
	var f = document.createElement("form");
	f.setAttribute("method","post");
	f.id = "rmc_ajouterParametres";
	var data1 = document.getElementById(idTab).getElementsByTagName("input");
	var data2 = document.getElementById(idTab).getElementsByTagName("select");
	var data3 = document.getElementById(idTab).getElementsByTagName("textarea");
	for(var i=0; i < data1.length; i++){
		var clone = data1[i].cloneNode(true);
		if ((clone.type == "checkbox") && (clone.checked)){
			clone.value = "on";
		}
		clone.type = "hidden";
		//console.log(clone);
		f.appendChild(clone);
	}
	for(var i=0; i < data2.length; i++){
		//var clone = data2[i].cloneNode(true);
		var clone = document.createElement("input");
		clone.type = "hidden";
		clone.value = data2[i].value;
		clone.name = data2[i].name;
		f.appendChild(clone);
	}
	for(var i=0; i < data3.length; i++){
		var clone = data3[i].cloneNode(true);
		clone.value = data2[i].innerHTML;
		clone.type = "hidden";
		f.appendChild(clone);
	}
	document.getElementById(idTab).appendChild(f);
	document.getElementById("rmc_ajouterParametres").submit();
}