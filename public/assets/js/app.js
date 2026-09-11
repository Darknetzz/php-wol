(function ($) {
  function baseUrl(path) {
    var base = window.WOL_BASE || '';
    if (path.charAt(0) !== '/') {
      path = '/' + path;
    }
    return base + path;
  }

  function setStatus(id, html) {
    $('#status-' + id).html(html);
  }

  function checkStatus(id) {
    setStatus(id, '<span class="text-body-secondary">Checking…</span>');
    return $.ajax({
      url: baseUrl('/api/status.php'),
      data: { id: id },
      dataType: 'json'
    }).done(function (data) {
      var color = data.online ? 'success' : 'danger';
      var html = '<span class="text-' + color + ' fw-semibold">' + $('<div>').text(data.label).html() + '</span>';
      if (data.detail) {
        html += '<pre class="status-detail text-' + color + ' mb-0 mt-1">' + $('<div>').text(data.detail).html() + '</pre>';
      }
      setStatus(id, html);
    }).fail(function () {
      setStatus(id, '<span class="text-warning">Error</span>');
    });
  }

  function updateAll() {
    $('#computers-table tr[data-id]').each(function () {
      checkStatus($(this).data('id'));
    });
  }

  function wake(id) {
    var $feedback = $('#wol-feedback');
    $feedback.html('<div class="alert alert-info mb-0">Sending magic packet…</div>');
    $.ajax({
      url: baseUrl('/api/wake.php'),
      data: { id: id },
      dataType: 'json'
    }).done(function (data) {
      var cls = data.ok ? 'success' : 'danger';
      $feedback.html('<div class="alert alert-' + cls + ' mb-0">' + $('<div>').text(data.message).html() + '</div>');
      checkStatus(id);
    }).fail(function (xhr) {
      var message = (xhr.responseJSON && xhr.responseJSON.message) || 'Wake request failed.';
      $feedback.html('<div class="alert alert-danger mb-0">' + $('<div>').text(message).html() + '</div>');
    });
  }

  $(function () {
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
