(function () {
	var toggle = 0;
	var count = 1;

	function ge(id) {
		return document.getElementById(id);
	}

	function createDivError() {
		var html =
				'<DIV id=errors>' +
					'<DIV></DIV>' +
					'<DIV style="display:none">' +
						'<H1>Error:</H1>' +
						'<DIV></DIV>' +
					'</DIV>' +
				'</DIV>';
		document.body.insertAdjacentHTML('afterBegin', html);
		var err = ge('errors');
		err.firstChild.onclick = function () {
			toggle = toggle ? 0 : 1;
			this.nextSibling.style.display = toggle ? 'block' : 'none';
		};

		err.style.cssText = "position:fixed; left:0px; top:0px; color:#800; border:#B66 solid 1px; background-color:#FBB; font:11px Tahoma; opacity: 0.97; z-index:2000000;";
		err.firstChild.style.cssText = "float:left; position:relative; left:1px; width:18px; padding:2px 0px 3px 0px; text-align:center; border-right:#B66 solid 1px; cursor:pointer;";
		err.firstChild.nextSibling.firstChild.style.cssText = "font-weight:bold; padding:2px 5px 3px 28px; margin-bottom:3px; border-bottom:#B66 solid 1px;";
		err.firstChild.nextSibling.firstChild.nextSibling.style.width = "400px";
	}

	window.onerror = function (msg, url, line) {
		if(!document.body)
			return;
		if(!ge('errors'))
			createDivError();
		var div = ge('errors').firstChild;
		div.nextSibling.firstChild.nextSibling.innerHTML += "<P style='margin:10px 8px;'><B style=font-weight:bold;>" + count + ".</B> " + msg + "<BR>" + url + " (<B>" + line + "</B>)";
		div.innerHTML = count++;
	};
})();
