/**
 * Created by meathill on 14-5-20.
 */
;(function (ns) {
  ns.Emulator = Backbone.View.extend({
    events: {
      'click .game-info p': 'gameDesc_clickHandler'
    },
    gameDesc_clickHandler: function (event) {
      $(event.currentTarget).toggleClass('active')
        .height(function () {
          return $(this).hasClass('active') ? this.scrollHeight : '3em';
        });
    }
  });
}(Nervenet.createNameSpace('gamepop.game')));