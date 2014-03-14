/**
 * Created by meathill on 14-3-14.
 */
;(function (ns) {
  var Model = Backbone.Model.extend({
    urlRoot: baseURL + 'games/article.php',
    parse: function (response) {
      return JSON.parse(response);
    }
  });

  ns.GameProfile = Backbone.View.extend({
    events: {

    },
    initialize: function () {
      this.template = Handlebars.compile(this.$('script').remove().html());

      var hash = location.hash
        , id = hash.substr(hash.lastIndexOf('/') + 1);
      this.model = new Model({
        id: id
      });
      this.model.on('sync', this.render, this);
      this.model.fetch();
    },
    render: function (model) {
      this.$el.html(this.template(model.toJSON()));
    }
  });
}(Nervenet.createNameSpace('dianjoy.page')));