(function($, $$, undef) {

window.addEvent("domready", function() {
	$$("form.tableextended").each(function(form) {
		new BBIT.TableExtended(form);
	});
});

if(!window.BBIT) BBIT = {};

BBIT.TableExtended = new Class({
	
//		Implements: [ Options, Events ],
//
//		options: {
//		},

	selectors: {},
	
	initialize: function(form) {
		this.table = form.getElement("input[name=FORM_SUBMIT]").get("value");
		if(!this.table) return undef;
		
		var self = this, req, fsReq, toggle, script, fieldset;

		script = function(script) { self.script = script.replace(/<!--|\/\/-->|<!\[CDATA\[\/\/>|<!\]\]>/g, ''); };
		toggle = function(event) {
			var value = this.toQueryString(), id = this.get("id");
			if(self.selectors[id] == value)
				return;

			event.target.blur();
			self.selectors[id] = value;
			self.current = this;
			id = id.substr(7);
			
			if(event = $("sub_" + id))
				event.destroy();

			req.post(Object.append(
				{
					isAjax: 1,
					REQUEST_TOKEN: REQUEST_TOKEN,
					action: "toggleSubpaletteExtended",
					FORM_SUBMIT: self.table,
					id: id
				},
				value.parseQueryString()
			));
		};
		
		req = new Request.Contao({
			url: window.location.href,
			method: "post",
			evalScripts: false,
			evalResponse: false,
			onRequest: AjaxRequest.displayBox.pass("Loading data …"),
			onComplete: AjaxRequest.hideBox,
			onSuccess: function(text) {
				if(text.length < 10)
					return;
				
				Elements.from(text.stripScripts(script), false)[0]
					.inject(self.current, 'after')
					.getElements("div.selector[id^=widget_]")
					.addEvent("click", toggle)
					.addEvent("change", toggle)
					.each(function(widget) {
						self.selectors[widget.get("id")] = widget.toQueryString();
					});

				$exec(self.script);

				Backend.hideTreeBody();
				Backend.addInteractiveHelp();
				Backend.addColorPicker();
				
				// HOOK
				window.fireEvent("subpalette");
			}
		});
		//req.success = function(text, xml) {	req.onSuccess(text.trim(), xml); };
		
		form.getElements("div.selector[id^=widget_]")
			.addEvent("click", toggle)
			.addEvent("change", toggle)
			.each(function(widget) {
				self.selectors[widget.get("id")] = widget.toQueryString();
			});
		
		fsReq = new Request.Contao({
			url: window.location.href
		});
		
		fieldset = function(event) {
			this.blur();
			var parent = this.getParent("fieldset").toggleClass("collapsed"), id = parent.get("id");
			
			if(!id) return false;
			
			fsReq.post({
				isAjax: 1,
				action: "toggleFieldset",
				id: id.substr(4),
				table: self.table,
				state: parent.hasClass("collapsed") ? "0" : "1",
				REQUEST_TOKEN: REQUEST_TOKEN
			});
				
			return false;
		};
		
		form.getElements("fieldset > legend").addEvent("click", fieldset);
	}

});
	
})(document.id, window.$$);