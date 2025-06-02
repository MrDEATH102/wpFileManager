jQuery(document).ready(function($) {
    // Accordion functionality
    $('.fam-accordion-header').on('click', function() {
        const $item = $(this).closest('.fam-accordion-item');
        const $content = $item.find('.fam-accordion-content');
        
        if ($item.hasClass('active')) {
            $item.removeClass('active');
            $content.slideUp(200);
        } else {
            $item.addClass('active');
            $content.slideDown(200);
        }
    });

    // File download tracking
    $('.fam-file-link').on('click', function(e) {
        const $link = $(this);
        const fileId = $link.data('file-id');
        
        if (fileId) {
            const data = {
                action: 'fam_track_download',
                nonce: famPublic.nonce,
                file_id: fileId
            };

            $.post(famPublic.ajaxurl, data);
        }
    });

    // Grid view hover effects
    $('.fam-grid-item').hover(
        function() {
            $(this).find('.fam-item-actions').fadeIn(200);
        },
        function() {
            $(this).find('.fam-item-actions').fadeOut(200);
        }
    );

    // Responsive grid adjustments
    function adjustGrid() {
        const $grid = $('.fam-grid');
        const windowWidth = $(window).width();
        
        if (windowWidth < 768) {
            $grid.css('grid-template-columns', 'repeat(auto-fill, minmax(150px, 1fr))');
        } else {
            $grid.css('grid-template-columns', 'repeat(auto-fill, minmax(200px, 1fr))');
        }
    }

    // Initial grid adjustment
    adjustGrid();

    // Adjust grid on window resize
    $(window).on('resize', adjustGrid);

    // File type icons
    function getFileIcon(fileType) {
        const icons = {
            'pdf': 'fa-file-pdf',
            'doc': 'fa-file-word',
            'docx': 'fa-file-word',
            'xls': 'fa-file-excel',
            'xlsx': 'fa-file-excel',
            'ppt': 'fa-file-powerpoint',
            'pptx': 'fa-file-powerpoint',
            'jpg': 'fa-file-image',
            'jpeg': 'fa-file-image',
            'png': 'fa-file-image',
            'gif': 'fa-file-image',
            'mp4': 'fa-file-video',
            'avi': 'fa-file-video',
            'mov': 'fa-file-video',
            'mp3': 'fa-file-audio',
            'wav': 'fa-file-audio',
            'zip': 'fa-file-archive',
            'rar': 'fa-file-archive',
            'txt': 'fa-file-alt',
            'default': 'fa-file'
        };

        const extension = fileType.toLowerCase().split('.').pop();
        return icons[extension] || icons.default;
    }

    // Set file icons
    $('.fam-file-link').each(function() {
        const $link = $(this);
        const fileName = $link.text().trim();
        const fileType = fileName.split('.').pop();
        const iconClass = getFileIcon(fileType);
        
        $link.find('.fam-icon').removeClass().addClass('fam-icon fas ' + iconClass);
    });

    // External link handling
    $('.fam-file-link[data-external="true"]').on('click', function(e) {
        const $link = $(this);
        const url = $link.attr('href');
        
        if (url) {
            window.open(url, '_blank');
            e.preventDefault();
        }
    });

    // File size formatting
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Display file sizes
    $('.fam-file-link').each(function() {
        const $link = $(this);
        const size = $link.data('size');
        
        if (size) {
            const formattedSize = formatFileSize(size);
            $link.append('<span class="file-size">(' + formattedSize + ')</span>');
        }
    });

    // Download count display
    $('.fam-file-link').each(function() {
        const $link = $(this);
        const downloads = $link.data('downloads');
        
        if (downloads !== undefined) {
            $link.append('<span class="download-count">' + downloads + ' downloads</span>');
        }
    });
}); 