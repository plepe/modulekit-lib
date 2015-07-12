var template_cache = {};

function twig_render_into_final(dom_node, template_id, data, callback, result) {
  if(typeof dom_node == "string")
    dom_node = document.getElementById(dom_node);

  dom_node.innerHTML = twig({ ref: template_id }).render(data);

  if(callback)
    callback(dom_node, template_id, data, result);

  // if there are still requests to process, do so
  if(template_cache[template_id] !== true) {
    var tc = template_cache[template_id];
    template_cache[template_id] = true;

    for(var i = 0; i < tc.length; i++) {
      var t = tc[i];
      twig_render_into_final(t[0], template_id, t[1], t[2]);
    }
  }
}

function twig_render_into(dom_node, template_id, data, callback) {
  if(!(template_id in template_cache)) {
    twig({
      id: template_id,
      href: 'templates/' + template_id,
      load: twig_render_into_final.bind(this, dom_node, template_id, data, callback)
    });

    template_cache[template_id] = [];
  }
  else if(template_cache[template_id] === true) {
    twig_render_into_final(dom_node, template_id, data, callback);
  }
  else {
    template_cache[template_id].push([ dom_node, data, callback ]);
  }
}

function twig_render_custom(template, data) {
  var t = twig({ data: template });
  return t.render(data);
}

register_hook("init", function() {
  call_hooks("twig_init");
});
