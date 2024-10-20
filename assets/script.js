document.addEventListener("DOMContentLoaded", function() {

    function addWatermark() {
        var plyrWrapper = document.querySelector('.plyr__video-wrapper');

        if (plyrWrapper && !document.getElementById('watermark')) {
            var watermark = document.createElement('div');
            watermark.id = 'watermark';
            watermark.textContent = tvwData.user_display_name; // Use localized user display name

            // Apply inline styles directly to the watermark element
            watermark.style.position = 'absolute';
            watermark.style.fontSize = '18px';
            watermark.style.color = 'grey';
            watermark.style.zIndex = '1000000';
            watermark.style.pointerEvents = 'none'; 
            watermark.style.top = '10px';
            watermark.style.left = '10px';

            plyrWrapper.style.position = 'relative';
            plyrWrapper.appendChild(watermark);
            
            moveWatermark();
        }
    }

    function moveWatermark() {
        const watermark = document.getElementById('watermark');
        setInterval(() => {
            if (watermark) {
                const randomTop = Math.random() * 80;
                const randomLeft = Math.random() * 80;
                watermark.style.top = randomTop + '%';
                watermark.style.left = randomLeft + '%';
            }
        }, 5000);
    }

    const observer = new MutationObserver((mutations, obs) => {
        var plyrWrapper = document.querySelector('.plyr__video-wrapper');
        if (plyrWrapper) {
            addWatermark();
            obs.disconnect();
        }
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    const watermarkObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.removedNodes.forEach(function(node) {
                if (node.id === 'watermark') {
                    // Re-add the watermark
                    addWatermark();

                    // Make AJAX call to ban user
                    
                        jQuery.ajax({
                            url: tvwData.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'tvw_ban_user',
                                user_id: tvwData.user_id
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Alert the user that they are banned
                                    alert("تم حظر حسابك لانتهاكك شروط الحماية الفكرية");
                                    console.log(response)
                                    // Redirect to the homepage
                                    //window.location.href = tvwData.home_url;
                                }
                            }
                        });
                    
                }
            });
        });
    });

    watermarkObserver.observe(document.body, {
        childList: true,
        subtree: true
    });

    function handleFullScreen() {
        var watermark = document.getElementById('watermark');
        if (document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement) {
            if (watermark) {
                watermark.style.position = 'fixed';
            }
        } else {
            if (watermark) {
                watermark.style.position = 'absolute';
            }
        }
    }

    document.addEventListener('fullscreenchange', handleFullScreen);
    document.addEventListener('webkitfullscreenchange', handleFullScreen);
    document.addEventListener('mozfullscreenchange', handleFullScreen);
    document.addEventListener('msfullscreenchange', handleFullScreen);
});
