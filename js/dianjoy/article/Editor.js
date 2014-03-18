/**
 * Created by meathill on 14-3-17.
 */
;(function (ns) {
  var Model = Backbone.Model.extend({
    urlRoot: 'api/article/detail.php'

  });
  ns.Editor = Backbone.View.extend({
    initialize: function () {
      var hash = location.hash
        , arr = hash.substr(2).split('/')
        , id = arr[3];
      this.model = new Model({
        id: id
      });
      this.model.on('sync', this.render, this);
      this.model.fetch();
    },
    render: function (model) {
      this.$('[name="id"]').val(model.id);
      this.$('[name="topic"]').val(model.get('topic'))
      this.$('textarea')
        .val(model.get('content'))
        .trigger('textinput');
    }
  });
}(Nervenet.createNameSpace('dianjoy.article')));