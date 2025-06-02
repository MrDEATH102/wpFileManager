jQuery(document).ready(function($) {
    // Modal handling
    function openModal(modalId) {
        $('#' + modalId).fadeIn(200);
    }

    function closeModal(modalId) {
        $('#' + modalId).fadeOut(200);
    }

    $('.fam-modal-close').on('click', function() {
        $(this).closest('.fam-modal').fadeOut(200);
    });

    $(document).on('click', '.fam-modal', function(e) {
        if ($(e.target).hasClass('fam-modal')) {
            $(this).fadeOut(200);
        }
    });

    // Add Archive
    $('#fam-add-archive').on('click', function() {
        openModal('fam-add-archive-modal');
    });

    $('#fam-add-archive-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'fam_create_archive',
            nonce: famAdmin.nonce,
            name: $('#archive-name').val(),
            description: $('#archive-description').val()
        };

        $.ajax({
            url: famAdmin.ajaxurl,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    location.reload();
                } else {
                    showFamError((response && response.data) ? response.data : 'خطای ناشناخته!');
                }
            },
            error: function(xhr) {
                let msg = 'خطای ارتباط با سرور (' + xhr.status + '): ';
                if (xhr.responseText && xhr.responseText.trim() === '0') {
                    msg += 'دسترسی یا nonce نامعتبر است یا یک خطای PHP رخ داده است.';
                } else {
                    msg += xhr.responseText || xhr.statusText;
                }
                showFamError(msg);
            }
        });
    });

    // --- Global state for current archive and folder ---
    let currentArchiveId = null;
    let currentFolderId = 0;

    // Archive selection
    $('.fam-archives-list li').on('click', function(e) {
        if (!$(e.target).hasClass('edit-archive') && !$(e.target).hasClass('delete-archive')) {
            const archiveId = $(this).data('archive-id');
            currentArchiveId = archiveId;
            currentFolderId = 0;
            loadArchiveContent(archiveId, 0);
            $('.fam-archives-list li').removeClass('active');
            $(this).addClass('active');
        }
    });

    // Edit Archive
    $(document).on('click', '.edit-archive', function(e) {
        e.stopPropagation();
        const archiveId = $(this).closest('li').data('archive-id');
        const archiveName = $(this).closest('li').find('.archive-name').text();
        // مقداردهی اولیه فیلدها
        $('#edit-archive-id').val(archiveId);
        $('#edit-archive-name').val(archiveName);
        // اگر توضیحات هم ذخیره شده بود، مقداردهی کن (در اینجا فرض می‌کنیم توضیحات در data-description ذخیره شده باشد)
        const archiveDesc = $(this).closest('li').data('description') || '';
        $('#edit-archive-description').val(archiveDesc);
        openModal('fam-edit-archive-modal');
    });

    // فرم ویرایش آرشیو
    $('#fam-edit-archive-form').on('submit', function(e) {
        e.preventDefault();
        const formData = {
            action: 'fam_edit_archive',
            nonce: famAdmin.nonce,
            id: $('#edit-archive-id').val(),
            name: $('#edit-archive-name').val(),
            description: $('#edit-archive-description').val()
        };
        $.post(famAdmin.ajaxurl, formData, function(response) {
            if (response.success) {
                closeModal('fam-edit-archive-modal');
                location.reload();
            } else {
                showFamError(response.data);
            }
        });
    });

    // Delete Archive
    $(document).on('click', '.delete-archive', function(e) {
        e.stopPropagation();
        if (confirm(famAdmin.i18n.confirmDelete)) {
            const archiveId = $(this).closest('li').data('archive-id');
            deleteItem('archive', archiveId);
        }
    });

    // Add Folder
    $(document).on('click', '#fam-add-folder', function() {
        const archiveId = $(this).data('archive-id');
        const parentId = $(this).data('parent-id');
        
        $('#folder-archive-id').val(archiveId);
        $('#folder-parent-id').val(parentId);
        openModal('fam-add-folder-modal');
    });

    $('#fam-add-folder-form').on('submit', function(e) {
        e.preventDefault();
        const formData = {
            action: 'fam_create_folder',
            nonce: famAdmin.nonce,
            archive_id: $('#folder-archive-id').val(),
            parent_id: $('#folder-parent-id').val(),
            name: $('#folder-name').val()
        };
        $.ajax({
            url: famAdmin.ajaxurl,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    closeModal('fam-add-folder-modal');
                    const archiveId = formData.archive_id;
                    const parentId = formData.parent_id && formData.parent_id !== '' ? formData.parent_id : 0;
                    loadArchiveContent(archiveId, parentId);
                } else {
                    showFamError((response && response.data) ? response.data : 'خطای ناشناخته!');
                }
            },
            error: function(xhr) {
                let msg = 'خطای ارتباط با سرور (' + xhr.status + '): ';
                if (xhr.responseText && xhr.responseText.trim() === '0') {
                    msg += 'دسترسی یا nonce نامعتبر است یا یک خطای PHP رخ داده است.';
                } else {
                    msg += xhr.responseText || xhr.statusText;
                }
                showFamError(msg);
            }
        });
    });

    // Upload File
    $(document).on('click', '#fam-upload-file', function() {
        const folderId = $(this).data('folder-id');
        $('#file-folder-id').val(folderId);
        openModal('fam-upload-file-modal');
    });

    $('#fam-upload-file-form').on('submit', function(e) {
        e.preventDefault();
        
        // Validation logic
        var fileInput = $('#file-upload')[0];
        var file = fileInput && fileInput.files.length > 0 ? fileInput.files[0] : null;
        var externalUrl = $('#file-external-url').val().trim();
        var externalName = $('#file-external-name').val().trim();

        if (!file && !externalUrl) {
            showFamError('Please select a file or provide an external URL.');
            return;
        }
        if (externalUrl && !externalName) {
            showFamError('Please provide a file name for the external URL.');
            return;
        }
        if (file && (externalUrl || externalName)) {
            // If a file is selected, ignore external fields
            $('#file-external-url').val('');
            $('#file-external-name').val('');
        }

        const formData = new FormData(this);
        formData.append('action', 'fam_upload_file');
        formData.append('nonce', famAdmin.nonce);

        $.ajax({
            url: famAdmin.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    closeModal('fam-upload-file-modal');
                    loadArchiveContent(currentArchiveId, currentFolderId);
                } else {
                    showFamError((response && response.data) ? response.data : 'خطای ناشناخته!');
                }
            },
            error: function(xhr) {
                let msg = 'خطای ارتباط با سرور (' + xhr.status + '): ';
                if (xhr.responseText && xhr.responseText.trim() === '0') {
                    msg += 'دسترسی یا nonce نامعتبر است یا یک خطای PHP رخ داده است.';
                } else {
                    msg += xhr.responseText || xhr.statusText;
                }
                showFamError(msg);
            }
        });
    });

    // Delete Item
    function deleteItem(type, id) {
        const data = {
            action: 'fam_delete_item',
            nonce: famAdmin.nonce,
            type: type,
            id: id
        };

        $.post(famAdmin.ajaxurl, data, function(response) {
            if (response.success) {
                if (type === 'archive') {
                    location.reload();
                } else {
                    const archiveId = $('.fam-archives-list li.active').data('archive-id');
                    loadArchiveContent(archiveId);
                }
            } else {
                showFamError(response.data);
            }
        });
    }

    // Load Archive Content
    function loadArchiveContent(archiveId, folderId = 0) {
        const data = {
            action: 'fam_load_archive',
            nonce: famAdmin.nonce,
            archive_id: archiveId,
            folder_id: folderId
        };

        $.post(famAdmin.ajaxurl, data, function(response) {
            if (response.success) {
                $('#fam-archive-content').html(response.data);
            } else {
                showFamError(response.data);
            }
        });
    }

    // Breadcrumb navigation
    $(document).on('click', '.fam-breadcrumb a', function(e) {
        e.preventDefault();
        const folderId = $(this).data('folder-id');
        const archiveId = $('.fam-archives-list li.active').data('archive-id');
        loadFolderContent(archiveId, folderId);
    });

    // Folder navigation
    $(document).on('click', '.fam-folder-item', function(e) {
        e.preventDefault();
        const folderId = $(this).data('folder-id');
        const archiveId = $('.fam-archives-list li.active').data('archive-id');
        loadFolderContent(archiveId, folderId);
    });

    function loadFolderContent(archiveId, folderId) {
        const data = {
            action: 'fam_load_folder',
            nonce: famAdmin.nonce,
            archive_id: archiveId,
            folder_id: folderId
        };

        $.post(famAdmin.ajaxurl, data, function(response) {
            if (response.success) {
                $('#fam-archive-content').html(response.data);
            } else {
                showFamError(response.data);
            }
        });
    }

    // Folder navigation (admin)
    $(document).on('click', '.fam-folder-item', function(e) {
        e.preventDefault();
        const folderId = $(this).data('folder-id');
        currentFolderId = folderId;
        const archiveId = currentArchiveId || $('.fam-archives-list li.active').data('archive-id');
        loadArchiveContent(archiveId, folderId);
    });

    // Breadcrumb navigation (admin)
    $(document).on('click', '.fam-breadcrumb-admin', function(e) {
        e.preventDefault();
        const folderId = $(this).data('folder-id');
        currentFolderId = folderId;
        const archiveId = $(this).data('archive-id') || currentArchiveId;
        loadArchiveContent(archiveId, folderId);
    });

    // Up a level button
    $(document).on('click', '.fam-breadcrumb-up', function(e) {
        e.preventDefault();
        const parentId = $(this).data('parent-id') || 0;
        currentFolderId = parentId;
        const archiveId = currentArchiveId || $('.fam-archives-list li.active').data('archive-id');
        loadArchiveContent(archiveId, parentId);
    });

    // حذف فولدر
    $(document).on('click', '.delete-folder', function(e) {
        e.stopPropagation();
        if (confirm(famAdmin.i18n.confirmDelete)) {
            const folderId = $(this).data('folder-id');
            deleteItem('folder', folderId);
        }
    });
    // حذف فایل
    $(document).on('click', '.delete-file', function(e) {
        e.stopPropagation();
        if (confirm(famAdmin.i18n.confirmDelete)) {
            const fileId = $(this).data('file-id');
            deleteItem('file', fileId);
        }
    });

    // ویرایش فولدر (جدید)
    $(document).on('click', '.fam-edit-folder', function(e) {
        e.stopPropagation();
        const folderId = $(this).data('folder-id');
        const folderName = $(this).data('folder-name');
        $('#edit-folder-id').val(folderId);
        $('#edit-folder-name').val(folderName);
        $('#fam-edit-folder-modal').show();
    });

    $('#fam-edit-folder-form').on('submit', function(e) {
        e.preventDefault();
        const formData = {
            action: 'fam_edit_folder',
            nonce: famAdmin.nonce,
            folder_id: $('#edit-folder-id').val(),
            name: $('#edit-folder-name').val()
        };
        $.ajax({
            url: famAdmin.ajaxurl,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    $('#fam-edit-folder-modal').hide();
                    loadArchiveContent(currentArchiveId, currentFolderId);
                } else {
                    showFamError((response && response.data) ? response.data : 'خطای ناشناخته!');
                }
            }
        });
    });

    // ویرایش فایل (جدید)
    $(document).on('click', '.fam-edit-file', function(e) {
        e.stopPropagation();
        const fileId = $(this).data('file-id');
        const fileName = $(this).data('file-name');
        const fileUrl = $(this).data('file-url');
        $('#edit-file-id').val(fileId);
        $('#edit-file-name').val(fileName);
        $('#edit-file-url').val(fileUrl);
        $('#fam-edit-file-modal').show();
    });

    $('#fam-edit-file-form').on('submit', function(e) {
        e.preventDefault();
        const formData = {
            action: 'fam_edit_file',
            nonce: famAdmin.nonce,
            file_id: $('#edit-file-id').val(),
            name: $('#edit-file-name').val(),
            url: $('#edit-file-url').val()
        };
        $.ajax({
            url: famAdmin.ajaxurl,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    $('#fam-edit-file-modal').hide();
                    loadArchiveContent(currentArchiveId, currentFolderId);
                } else {
                    showFamError((response && response.data) ? response.data : 'خطای ناشناخته!');
                }
            },
            error: function(xhr) {
                let msg = 'خطای ارتباط با سرور (' + xhr.status + '): ';
                if (xhr.responseText && xhr.responseText.trim() === '0') {
                    msg += 'دسترسی یا nonce نامعتبر است یا یک خطای PHP رخ داده است.';
                } else {
                    msg += xhr.responseText || xhr.statusText;
                }
                showFamError(msg);
            }
        });
    });

    // بهبود استایل fam-item-actions
    $(document).on('mouseenter', '.fam-item', function() {
        $(this).find('.fam-item-actions').css({'display':'flex'});
    });
    $(document).on('mouseleave', '.fam-item', function() {
        $(this).find('.fam-item-actions').css({'display':'none'});
    });

    // تابع نمایش خطا در بالای صفحه
    function showFamError(msg) {
        let errDiv = $('#fam-error-message');
        if (!errDiv.length) {
            errDiv = $('<div id="fam-error-message" style="background:#ffeaea;color:#d32f2f;padding:10px 20px;margin-bottom:15px;border-radius:4px;font-weight:bold;display:none;"></div>');
            $('.fam-content').prepend(errDiv);
        }
        errDiv.text(msg).fadeIn(200);
        setTimeout(function(){ errDiv.fadeOut(500); }, 6000);
    }

    // Open edit modal
    $(document).on('click', '.fam-edit-folder', function(){
        const id = $(this).data('folder-id');
        const name = $(this).closest('li').find('.folder-name').text().trim();
        $('#edit-folder-id').val(id);
        $('#edit-folder-name').val(name);
        $('#fam-edit-folder-modal').show();
    });

    // Close modal
    $('.fam-modal-close').on('click', function(){
        $('#fam-edit-folder-modal').hide();
    });

    // Submit edit form via AJAX
    $('#fam-edit-folder-form').on('submit', function(e){
        e.preventDefault();
        $.post(famAdmin.ajaxurl, {
            action: 'fam_edit_folder',
            nonce: famAdmin.nonce,
            folder_id: $('#edit-folder-id').val(),
            name: $('#edit-folder-name').val()
        }, function(res){
            if(res.success){
                location.reload();
            } else {
                alert(res.data || 'خطای نامشخص');
            }
        });
    });
}); 