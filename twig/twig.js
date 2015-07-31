var template_cache = {};

function twig_render_into_final(dom_node, template_id, data, callback, result) {
  if(!(template_id in template_cache))
    template_cache[template_id] = twig({ data: result });

  dom_node.innerHTML = template_cache[template_id].render(data);

  if(callback)
    callback(dom_node, template_id, data, result);
}

function twig_render_into(dom_node, template_id, data, callback) {
  if(template_id in template_cache) {
    twig_render_into_final(dom_node, template_id, data, callback);
  }
  else {
    var req = new XMLHttpRequest();
    req.open('GET', 'templates/' + template_id, true);
    req.onreadystatechange = function(req, callnext) {
      if(req.readyState == 4)
        callnext(req.responseText);
    }.bind(this, req, twig_render_into_final.bind(this, dom_node, template_id, data, callback));
    req.send(null);
  }
}

function twig_render_custom(template, data) {
  var t = twig({ data: template });
  return t.render(data);
}

register_hook("init", function() {
  call_hooks("twig_init");
});
