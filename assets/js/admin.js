
(function ($) {
  function updatePreview($field, url) {
    var $preview = $field.find('.wpqr-media-preview');
    if (url) {
      $preview
        .removeClass('is-empty')
        .html('<img src="' + url + '" alt="">');
    } else {
      $preview
        .addClass('is-empty')
        .text('Aucune image sélectionnée');
    }
  }

  $(document).on('click', '.wpqr-media-select', function (event) {
    event.preventDefault();
    var $field = $(this).closest('.wpqr-media-field');
    var $input = $field.find('.wpqr-media-input');
    var frame = wp.media({
      title: 'Choisir une image',
      library: { type: 'image' },
      button: { text: 'Utiliser cette image' },
      multiple: false
    });

    frame.on('select', function () {
      var attachment = frame.state().get('selection').first().toJSON();
      $input.val(attachment.id || '');
      updatePreview($field, attachment.url || '');
    });

    frame.open();
  });

  $(document).on('click', '.wpqr-media-remove', function (event) {
    event.preventDefault();
    var $field = $(this).closest('.wpqr-media-field');
    $field.find('.wpqr-media-input').val('');
    updatePreview($field, '');
  });
})(jQuery);
