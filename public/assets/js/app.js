(function ($) {
  var sortState = { key: 'hostname', dir: 'asc' };
  var hostModal = null;

  function baseUrl(path) {
    var base = window.WOL_BASE || '';
    if (path.charAt(0) !== '/') {
      path = '/' + path;
    }
    return base + path;
  }

  function refreshIcons(root) {
    if (!window.lucide || typeof window.lucide.createIcons !== 'function') {
      return;
    }
    var scope = root || document;
    // Only convert fresh <i data-lucide> placeholders. Rendered SVGs keep
    // data-lucide, and re-running createIcons on them mutates the open modal
    // and lets Bootstrap's focus trap steal focus from the hostname field.
    if (!scope.querySelector('i[data-lucide]')) {
      return;
    }
    var restored = [];
    Array.prototype.forEach.call(document.querySelectorAll('svg[data-lucide]'), function (svg) {
      restored.push([svg, svg.getAttribute('data-lucide')]);
      svg.removeAttribute('data-lucide');
    });
    window.lucide.createIcons();
    restored.forEach(function (pair) {
      pair[0].setAttribute('data-lucide', pair[1]);
    });
  }

  function iconMarkup(name) {
    return '<i data-lucide="' + name + '" class="lucide-icon" aria-hidden="true"></i>';
  }

  function escapeHtml(text) {
    return $('<div>').text(text == null ? '' : String(text)).html();
  }

  function setFeedback(type, message) {
    var names = { success: 'circle-check', danger: 'circle-alert', info: 'send', warning: 'triangle-alert' };
    $('#wol-feedback').html(
      '<div class="alert alert-' +
        type +
        ' mb-0 d-flex align-items-center gap-2">' +
        iconMarkup(names[type] || 'info') +
        '<span>' +
        escapeHtml(message) +
        '</span></div>'
    );
    refreshIcons();
  }

  function setStatus(id, html, label) {
    var $cell = $('#status-' + id);
    $cell.html(html);
    if (label !== undefined) {
      $cell.attr('data-status-label', label);
    }
    refreshIcons();
  }

  function formatIpCell(ip, resolved) {
    if (!ip) {
      return '<span class="text-body-secondary">—</span>';
    }
    if (resolved) {
      return (
        '<span title="Resolved from hostname">' +
        escapeHtml(ip) +
        ' <span class="text-body-secondary small">(DNS)</span></span>'
      );
    }
    return escapeHtml(ip);
  }

  function formatMacCell(mac) {
    if (!mac) {
      return '<span class="text-body-secondary">—</span>';
    }
    return '<code>' + escapeHtml(mac) + '</code>';
  }

  function wakeButtonHtml(id, hasMac) {
    return (
      '<button type="button" class="btn btn-sm btn-secondary btn-wake d-inline-flex align-items-center gap-1" data-id="' +
      id +
      '"' +
      (hasMac ? '' : ' disabled title="No MAC address — Wake-on-LAN disabled"') +
      '>' +
      iconMarkup('power') +
      ' Wake</button>'
    );
  }

  function ensureEmptyRow() {
    var $tbody = $('#computers-table tbody');
    if ($tbody.find('tr[data-id]').length === 0 && $tbody.find('tr.empty-row').length === 0) {
      $tbody.html(
        '<tr class="empty-row"><td colspan="5" class="text-body-secondary">No devices yet. Click <strong>New device</strong> to add one.</td></tr>'
      );
    }
  }

  function removeEmptyRow() {
    $('#computers-table tbody tr.empty-row').remove();
  }

  function rowHtml(computer) {
    var id = computer.id;
    var hasMac = !!computer.mac;
    return (
      '<tr data-id="' +
      id +
      '" data-hostname="' +
      escapeHtml(computer.hostname) +
      '" data-ip="' +
      escapeHtml(computer.ip || '') +
      '" data-mac="' +
      escapeHtml(computer.mac || '') +
      '">' +
      '<td><button type="button" class="btn btn-link p-0 host-edit-link" data-id="' +
      id +
      '">' +
      escapeHtml(computer.hostname) +
      '</button></td>' +
      '<td class="host-ip">' +
      formatIpCell(computer.ip || '', false) +
      '</td>' +
      '<td class="host-mac">' +
      formatMacCell(computer.mac || '') +
      '</td>' +
      '<td id="status-' +
      id +
      '" class="status-cell" data-status-label="">…</td>' +
      '<td class="text-end">' +
      wakeButtonHtml(id, hasMac) +
      '</td></tr>'
    );
  }

  function upsertRow(computer) {
    removeEmptyRow();
    var $existing = $('#computers-table tr[data-id="' + computer.id + '"]');
    if ($existing.length) {
      $existing.replaceWith(rowHtml(computer));
    } else {
      $('#computers-table tbody').append(rowHtml(computer));
    }
    refreshIcons();
    applySort();
    checkStatus(computer.id);
  }

  function checkStatus(id) {
    var $row = $('#computers-table tr[data-id="' + id + '"]');
    if (!$row.length) {
      return;
    }

    setStatus(
      id,
      '<span class="text-body-secondary d-inline-flex align-items-center gap-1">' +
        iconMarkup('loader-circle') +
        ' Checking…</span>',
      'Checking'
    );

    return $.ajax({
      url: baseUrl('/api/status.php'),
      data: { id: id },
      dataType: 'json'
    })
      .done(function (data) {
        var color = data.ping === false ? 'secondary' : data.online ? 'success' : 'danger';
        var name =
          data.ping === false ? 'circle-minus' : data.online ? 'circle-check' : 'circle-x';
        var html =
          '<span class="text-' +
          color +
          ' fw-semibold d-inline-flex align-items-center gap-1">' +
          iconMarkup(name) +
          ' ' +
          escapeHtml(data.label) +
          '</span>';
        if (data.detail) {
          html +=
            '<pre class="status-detail text-' +
            color +
            ' mb-0 mt-1">' +
            escapeHtml(data.detail) +
            '</pre>';
        }
        setStatus(id, html, data.label || '');

        var storedIp = $row.attr('data-ip') || '';
        if (!storedIp) {
          $row.find('.host-ip').html(formatIpCell(data.ip || '', !!data.resolved && !!data.ip));
        }
      })
      .fail(function () {
        setStatus(
          id,
          '<span class="text-warning d-inline-flex align-items-center gap-1">' +
            iconMarkup('triangle-alert') +
            ' Error</span>',
          'Error'
        );
      });
  }

  function updateAll() {
    $('#computers-table tr[data-id]').each(function () {
      checkStatus($(this).data('id'));
    });
  }

  function wake(id) {
    setFeedback('info', 'Sending magic packet…');
    $.ajax({
      url: baseUrl('/api/wake.php'),
      data: { id: id },
      dataType: 'json'
    })
      .done(function (data) {
        setFeedback(data.ok ? 'success' : 'danger', data.message);
        checkStatus(id);
      })
      .fail(function (xhr) {
        var message =
          (xhr.responseJSON && xhr.responseJSON.message) || 'Wake request failed.';
        setFeedback('danger', message);
      });
  }

  function sortValue($row, key) {
    if (key === 'status') {
      return ($row.find('.status-cell').attr('data-status-label') || $row.find('.status-cell').text() || '')
        .trim()
        .toLowerCase();
    }
    if (key === 'hostname') {
      return ($row.attr('data-hostname') || '').toLowerCase();
    }
    if (key === 'ip') {
      return ($row.attr('data-ip') || $row.find('.host-ip').text() || '').trim().toLowerCase();
    }
    if (key === 'mac') {
      return ($row.attr('data-mac') || '').toLowerCase();
    }
    return '';
  }

  function applySort() {
    var key = sortState.key;
    var dir = sortState.dir;
    var $tbody = $('#computers-table tbody');
    var $rows = $tbody.find('tr[data-id]').get();

    $rows.sort(function (a, b) {
      var A = sortValue($(a), key);
      var B = sortValue($(b), key);
      if (A < B) {
        return dir === 'asc' ? -1 : 1;
      }
      if (A > B) {
        return dir === 'asc' ? 1 : -1;
      }
      return 0;
    });

    $.each($rows, function (_, row) {
      $tbody.append(row);
    });

    $('#computers-table thead th.sortable').removeClass('sort-asc sort-desc');
    $('#computers-table thead th.sortable[data-sort="' + key + '"]').addClass(
      dir === 'asc' ? 'sort-asc' : 'sort-desc'
    );
  }

  function showModalError(message) {
    var $err = $('#host-modal-error');
    if (!message) {
      $err.addClass('d-none').find('span').text('');
      return;
    }
    $err.removeClass('d-none').find('span').text(message);
  }

  function openHostModal(computer) {
    showModalError('');
    var isNew = !computer || !computer.id;
    $('#host-modal-heading').text(isNew ? 'New device' : 'Edit device');
    $('#host-id').val(isNew ? '' : computer.id);
    $('#host-hostname').val(isNew ? '' : computer.hostname || '');
    $('#host-ip').val(isNew ? '' : computer.ip || '');
    $('#host-mac').val(isNew ? '' : computer.mac || '');
    $('#host-delete-btn').toggleClass('d-none', isNew);
    if (!hostModal) {
      hostModal = new bootstrap.Modal(document.getElementById('host-modal'));
    }
    hostModal.show();
  }

  function openEditById(id) {
    var $row = $('#computers-table tr[data-id="' + id + '"]');
    if ($row.length) {
      openHostModal({
        id: id,
        hostname: $row.attr('data-hostname') || '',
        ip: $row.attr('data-ip') || '',
        mac: $row.attr('data-mac') || ''
      });
      return;
    }

    $.ajax({
      url: baseUrl('/api/computer.php'),
      data: { id: id },
      dataType: 'json'
    })
      .done(function (data) {
        if (data.ok) {
          openHostModal(data.computer);
        } else {
          setFeedback('danger', data.message || 'Could not load device.');
        }
      })
      .fail(function () {
        setFeedback('danger', 'Could not load device.');
      });
  }

  function saveHost(e) {
    e.preventDefault();
    showModalError('');

    var payload = {
      csrf_token: window.WOL_CSRF || '',
      action: 'save',
      id: $('#host-id').val() || '',
      hostname: $('#host-hostname').val(),
      ip: $('#host-ip').val(),
      mac: $('#host-mac').val()
    };

    $.ajax({
      url: baseUrl('/api/computer.php'),
      method: 'POST',
      data: payload,
      dataType: 'json'
    })
      .done(function (data) {
        if (!data.ok) {
          showModalError(data.message || 'Save failed.');
          return;
        }
        upsertRow(data.computer);
        if (hostModal) {
          hostModal.hide();
        }
        setFeedback('success', data.message);
      })
      .fail(function (xhr) {
        var message =
          (xhr.responseJSON && xhr.responseJSON.message) || 'Save failed.';
        showModalError(message);
      });
  }

  function deleteHost() {
    var id = $('#host-id').val();
    var name = $('#host-hostname').val() || 'this device';
    if (!id) {
      return;
    }
    if (!window.confirm('Delete ' + name + '?')) {
      return;
    }

    $.ajax({
      url: baseUrl('/api/computer.php'),
      method: 'POST',
      data: {
        csrf_token: window.WOL_CSRF || '',
        action: 'delete',
        id: id
      },
      dataType: 'json'
    })
      .done(function (data) {
        if (!data.ok) {
          showModalError(data.message || 'Delete failed.');
          return;
        }
        $('#computers-table tr[data-id="' + id + '"]').remove();
        ensureEmptyRow();
        if (hostModal) {
          hostModal.hide();
        }
        setFeedback('success', data.message);
      })
      .fail(function (xhr) {
        var message =
          (xhr.responseJSON && xhr.responseJSON.message) || 'Delete failed.';
        showModalError(message);
      });
  }

  $(function () {
    $('#host-modal').on('shown.bs.modal', function () {
      var input = document.getElementById('host-hostname');
      if (input) {
        input.focus();
        input.select();
      }
    });
    refreshIcons();
    applySort();
    updateAll();
    setInterval(updateAll, 10000);

    $('#btn-update-all').on('click', function (e) {
      e.preventDefault();
      updateAll();
    });

    $('#btn-new-device').on('click', function (e) {
      e.preventDefault();
      openHostModal(null);
    });

    $(document).on('click', '.btn-wake', function (e) {
      e.preventDefault();
      if ($(this).prop('disabled')) {
        return;
      }
      wake($(this).data('id'));
    });

    $(document).on('click', '.host-edit-link', function (e) {
      e.preventDefault();
      openEditById($(this).data('id'));
    });

    $('#host-form').on('submit', saveHost);
    $('#host-delete-btn').on('click', deleteHost);

    $('#computers-table thead').on('click keydown', 'th.sortable', function (e) {
      if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') {
        return;
      }
      e.preventDefault();
      var key = $(this).data('sort');
      if (sortState.key === key) {
        sortState.dir = sortState.dir === 'asc' ? 'desc' : 'asc';
      } else {
        sortState.key = key;
        sortState.dir = 'asc';
      }
      applySort();
    });
  });
})(jQuery);
