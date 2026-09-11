(function ($) {
  function baseUrl(path) {
    var base = window.WOL_BASE || '';
    if (path.charAt(0) !== '/') {
      path = '/' + path;
    }
    return base + path;
  }

  function refreshIcons() {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
      window.lucide.createIcons();
    }
  }

  function iconMarkup(name) {
    return '<i data-lucide="' + name + '" class="lucide-icon" aria-hidden="true"></i>';
  }

  function setStatus(id, html) {
    $('#status-' + id).html(html);
    refreshIcons();
  }

  function checkStatus(id) {
    setStatus(
      id,
      '<span class="text-body-secondary d-inline-flex align-items-center gap-1">' +
        iconMarkup('loader-circle') +
        ' Checking…</span>'
    );
    return $.ajax({
      url: baseUrl('/api/status.php'),
      data: { id: id },
      dataType: 'json'
    }).done(function (data) {
      var color = data.online ? 'success' : 'danger';
      var name = data.online ? 'circle-check' : 'circle-x';
      var html =
        '<span class="text-' + color + ' fw-semibold d-inline-flex align-items-center gap-1">' +
        iconMarkup(name) +
        ' ' +
        $('<div>').text(data.label).html() +
        '</span>';
      if (data.detail) {
        html +=
          '<pre class="status-detail text-' +
          color +
          ' mb-0 mt-1">' +
          $('<div>').text(data.detail).html() +
          '</pre>';
      }
      setStatus(id, html);
    }).fail(function () {
      setStatus(
        id,
        '<span class="text-warning d-inline-flex align-items-center gap-1">' +
          iconMarkup('triangle-alert') +
          ' Error</span>'
      );
    });
  }

  function updateAll() {
    $('#computers-table tr[data-id]').each(function () {
      checkStatus($(this).data('id'));
    });
  }

  function wake(id) {
    var $feedback = $('#wol-feedback');
    $feedback.html(
      '<div class="alert alert-info mb-0 d-flex align-items-center gap-2">' +
        iconMarkup('send') +
        '<span>Sending magic packet…</span></div>'
    );
    refreshIcons();
    $.ajax({
      url: baseUrl('/api/wake.php'),
      data: { id: id },
      dataType: 'json'
    }).done(function (data) {
      var cls = data.ok ? 'success' : 'danger';
      var name = data.ok ? 'circle-check' : 'circle-alert';
      $feedback.html(
        '<div class="alert alert-' +
          cls +
          ' mb-0 d-flex align-items-center gap-2">' +
          iconMarkup(name) +
          '<span>' +
          $('<div>').text(data.message).html() +
          '</span></div>'
      );
      refreshIcons();
      checkStatus(id);
    }).fail(function (xhr) {
      var message = (xhr.responseJSON && xhr.responseJSON.message) || 'Wake request failed.';
      $feedback.html(
        '<div class="alert alert-danger mb-0 d-flex align-items-center gap-2">' +
          iconMarkup('circle-alert') +
          '<span>' +
          $('<div>').text(message).html() +
          '</span></div>'
      );
      refreshIcons();
    });
  }

  $(function () {
    refreshIcons();
    updateAll();
    setInterval(updateAll, 10000);

    $('#btn-update-all').on('click', function (e) {
      e.preventDefault();
      updateAll();
    });

    $(document).on('click', '.btn-wake', function (e) {
      e.preventDefault();
      wake($(this).data('id'));
    });
  });
})(jQuery);
