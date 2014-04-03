/**
 * Created by meathill on 14-3-28.
 */
;(function (ns) {
  ns.HomepageNav = Backbone.View.extend({
    events: {

    },
    initialize: function () {
      this.template = Handlebars.compile(this.$('script').remove().html());

      var spec = this.$el.data();
      this.collection = dianjoy.model.ListCollection.createInstance(null, {
        url: spec.url + '/' + this.model.get('path')
      });
      this.collection.on('reset', this.render, this);
      this.collection.on('add', this.collection_addHandler, this);
      this.collection.on('remove', this.collection_removeHandler, this);
      this.collection.on('change', this.collection_changeHandler, this);
      this.collection.fetch(this.model.toJSON());
    },
    render: function (collection) {
      this.$el.html(this.template({list: collection.toJSON()}));
    },
    createItem: function (model) {
      return this.template({list: [model.toJSON()]});
    },
    collection_addHandler: function (model) {
      this.$el.append(this.createItem(model));
    },
    collection_changeHandler: function (model) {
      var item = this.$('#' + ('id' in model.changed ? model.cid : model.id));
      if (item.length) {
        item.replaceWith(this.createItem(model));
      } else {
        this.$el.append(this.createItem(model));
      }
    },
    collection_removeHandler: function (model) {
      this.$('#' + model.id).fadeOut(function () {
        $(this).remove();
      });
    }
  });
}(Nervenet.createNameSpace('gamepop.game')));