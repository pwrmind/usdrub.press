var icons = ["❤️", "🤘", "🔥", "💰"];

var t = setInterval(function() {
	var icon = icons.shift();
	document.getElementById("icon").innerText = icon;
	icons.push(icon);
}, 2000);