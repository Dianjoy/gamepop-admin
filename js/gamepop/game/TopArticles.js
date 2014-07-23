/**
 * Created by 路佳 on 14-4-28.
 */
;(function (ns) {
  ns.TopArticles = Backbone.View.extend({
    initialize: function () {
      this.template = Handlebars.compile(this.$('script').remove().html());

      var spec = this.$el.data();
      this.collection = dianjoy.model.ListCollection.createInstance(null, {
        url: spec.url + '/' + this.model.get('path'),
        id: spec.collectionId
      });
      this.collection.on('reset', this.render, this);
      this.collection.on('change', this.collection_changeHandler, this);
      this.collection.on('sort', this.collection_sortHandler, this);
    },
    remove: function () {
      this.collection.off();
      Backbone.View.prototype.remove.call(this);
      dianjoy.model.ListCollection.destroyInstance(this.collection.url);
    },
    render: function (collection) {
      this.$el.html(this.template({list: collection.toJSON()}));
    },
    createItem: function (model) {
      return this.template({list: [model.toJSON()]});
    },
    collection_changeHandler: function (model) {
      var item = this.$('#top-' + model.id);
      if ('is_top' in model.changed) {
        item.toggleClass('hide', !model.get('is_top'));
        return;
      }
      item.replaceWith(this.createItem(model));
    },
    collection_sortHandler: function (model, index) {
      var item = this.$('#top-' + model.id);
      item = item.length > 0 ? item : this.$('#top-' + model.cid);
      if (item.index() < index) {
        item.insertAfter(this.$el.children().eq(index));
      } else {
        item.insertBefore(this.$el.children().eq(index));
      }
    }
  });
}(Nervenet.createNameSpace('gamepop.game')));