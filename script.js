// Auto refresh interval
let autoRefreshInterval;

// Copy email to clipboard
function copyEmail(event) {
    const emailInput = document.getElementById('tempEmail');
    const emailText = emailInput.value;
    
    navigator.clipboard.writeText(emailText).then(() => {
        const btn = event ? event.target : document.querySelector('.btn-copy');
        const originalText = btn.textContent;
        btn.textContent = '✓ Copied!';
        btn.style.background = '#4CAF50';
        
        setTimeout(() => {
            btn.textContent = originalText;
            btn.style.background = '';
        }, 2000);
    }).catch(() => {
        // Fallback for older browsers
        emailInput.select();
        document.execCommand('copy');
        alert('Email disalin!');
    });
}

// Generate new email
function generateNew() {
    if (!confirm('Generate email baru? Email lama akan tetap bisa diakses dengan token.')) {
        return;
    }
    
    fetch('api.php?action=generate')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update email
                document.getElementById('tempEmail').value = data.email;
                
                // Update token
                if (data.token) {
                    document.getElementById('tokenInput').value = data.token;
                }
                
                // Update token URL
                if (data.url) {
                    document.getElementById('tokenUrl').textContent = data.url;
                } else if (data.token) {
                    // Fallback: construct URL manually
                    const baseUrl = window.location.origin + window.location.pathname;
                    document.getElementById('tokenUrl').textContent = baseUrl + '?token=' + data.token;
                }
                
                // Refresh inbox
                refreshInbox();
                
                // Show success message
                alert('Email baru berhasil dibuat!\nToken: ' + data.token);
            } else {
                alert('Gagal membuat email baru: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat membuat email baru');
        });
}

// Copy token
function copyToken(event) {
    const tokenInput = document.getElementById('tokenInput');
    const tokenText = tokenInput.value;
    
    navigator.clipboard.writeText(tokenText).then(() => {
        const btn = event ? event.target : document.querySelector('.btn-copy-token');
        const originalText = btn.textContent;
        btn.textContent = '✓ Copied!';
        btn.style.background = '#4CAF50';
        
        setTimeout(() => {
            btn.textContent = originalText;
            btn.style.background = '';
        }, 2000);
    }).catch(() => {
        // Fallback for older browsers
        tokenInput.select();
        document.execCommand('copy');
        alert('Token disalin!');
    });
}

// Copy token URL
function copyTokenUrl(event) {
    const tokenUrl = document.getElementById('tokenUrl').textContent;
    navigator.clipboard.writeText(tokenUrl).then(() => {
        const btn = event ? event.target : document.querySelector('.btn-copy-url');
        const originalText = btn.textContent;
        btn.textContent = '✓';
        btn.style.background = '#4CAF50';
        
        setTimeout(() => {
            btn.textContent = originalText;
            btn.style.background = '';
        }, 2000);
    }).catch(() => {
        alert('URL disalin!');
    });
}

// Toggle restore form
function toggleRestoreForm() {
    const form = document.getElementById('restoreForm');
    form.style.display = form.style.display === 'none' ? 'flex' : 'none';
}

// Restore email
function restoreEmail() {
    const token = document.getElementById('restoreToken').value.trim();
    
    if (!token) {
        alert('Masukkan token terlebih dahulu');
        return;
    }
    
    fetch('api.php?action=restore', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'token=' + encodeURIComponent(token)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('tempEmail').value = data.email;
            document.getElementById('tokenInput').value = data.token;
            document.getElementById('tokenUrl').textContent = window.location.origin + '/?token=' + data.token;
            document.getElementById('restoreForm').style.display = 'none';
            document.getElementById('restoreToken').value = '';
            refreshInbox();
            alert('Email berhasil dipulihkan!');
        } else {
            alert(data.message || 'Token tidak valid');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan');
    });
}

// Refresh inbox
function refreshInbox() {
    fetch('api.php?action=get_emails')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateEmailList(data.emails);
                document.querySelector('.inbox-header h2').textContent = `Inbox (${data.count})`;
            }
        })
        .catch(error => console.error('Error:', error));
}

// Update email list
function updateEmailList(emails) {
    const emailList = document.getElementById('emailList');
    
    if (emails.length === 0) {
        emailList.innerHTML = `
            <div class="no-emails">
                <p>📭 Tidak ada email masuk</p>
                <p class="hint">Email akan muncul di sini secara otomatis</p>
            </div>
        `;
        return;
    }
    
    emailList.innerHTML = emails.map(email => `
        <div class="email-item" onclick="viewEmail(${email.id})">
            <div class="email-from">
                <strong>Dari:</strong> ${escapeHtml(email.from_email)}
            </div>
            <div class="email-subject">
                ${escapeHtml(email.subject)}
            </div>
            <div class="email-date">
                ${formatDate(email.received_at, email.timestamp)}
            </div>
        </div>
    `).join('');
}

// View email details
function viewEmail(id) {
    fetch(`api.php?action=get_email&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showEmailModal(data.email);
            }
        })
        .catch(error => console.error('Error:', error));
}

// Show email modal
function showEmailModal(email) {
    const modal = document.getElementById('emailModal');
    const content = document.getElementById('emailContent');
    
    // Format body - detect if HTML or plain text
    let bodyContent = email.body;
    let isHtml = false;
    let isPartialHtml = false; // HTML tags in plain text
    
    // Check if content is proper HTML (with DOCTYPE or html tag)
    if (bodyContent.includes('<html') || bodyContent.includes('<!DOCTYPE') || 
        bodyContent.includes('</html>')) {
        isHtml = true;
    }
    // Check if content has HTML tags but might be plain text with HTML fragments
    else if (bodyContent.includes('<div') || bodyContent.includes('<table') ||
        bodyContent.includes('<p>') || bodyContent.includes('<br>') ||
        bodyContent.includes('<br />') || bodyContent.includes('<br/>') ||
        bodyContent.includes('<a ') || bodyContent.includes('<img') ||
        bodyContent.includes('<span') || bodyContent.includes('<strong') ||
        bodyContent.includes('<em>') || bodyContent.includes('<h1') ||
        bodyContent.includes('<h2') || bodyContent.includes('<h3')) {
        // Check if it's a complete HTML structure or just fragments
        const htmlTagCount = (bodyContent.match(/<[^>]+>/g) || []).length;
        const closingTagCount = (bodyContent.match(/<\/[^>]+>/g) || []).length;
        
        // If has many tags and closing tags, treat as HTML
        if (htmlTagCount > 3 && closingTagCount > 0) {
            isHtml = true;
        } else {
            // Might be plain text with HTML fragments
            isPartialHtml = true;
            isHtml = true; // Treat as HTML to render it
        }
    }
    
    // Extract clean text from HTML
    let cleanText = bodyContent;
    if (isHtml) {
        // Create temporary div to parse HTML
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = bodyContent;
        
        // Remove style tags and their content
        const styleTags = tempDiv.querySelectorAll('style');
        styleTags.forEach(tag => tag.remove());
        
        // Remove script tags
        const scriptTags = tempDiv.querySelectorAll('script');
        scriptTags.forEach(tag => tag.remove());
        
        // Get text content
        cleanText = tempDiv.textContent || tempDiv.innerText || '';
        
        // Clean up excessive whitespace and newlines
        cleanText = cleanText
            .replace(/\n\s*\n\s*\n/g, '\n\n') // Replace 3+ newlines with 2
            .replace(/[ \t]+/g, ' ') // Replace multiple spaces/tabs with single space
            .replace(/^\s+/gm, '') // Remove leading whitespace from each line
            .trim();
    } else {
        // For plain text, preserve formatting
        cleanText = bodyContent;
    }
    
    // Create view toggle buttons
    const viewToggle = isHtml ? `
        <div class="view-toggle">
            <button onclick="toggleEmailView('html')" id="btnHtml" class="active">🌐 Tampilan HTML</button>
            <button onclick="toggleEmailView('clean')" id="btnClean">✨ Teks Bersih</button>
            <button onclick="toggleEmailView('raw')" id="btnRaw">📝 Kode Asli</button>
        </div>
    ` : '';
    
    // Default view: HTML if available, otherwise plain text
    let defaultView = '';
    if (isHtml) {
        // Wrap partial HTML in proper HTML structure
        let htmlToRender = bodyContent;
        if (isPartialHtml || (!bodyContent.includes('<html') && !bodyContent.includes('<!DOCTYPE'))) {
            htmlToRender = `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            line-height: 1.6;
            color: #333;
        }
    </style>
</head>
<body>
${bodyContent}
</body>
</html>`;
        }
        defaultView = renderHtmlEmail(htmlToRender);
    } else {
        // For plain text, preserve line breaks
        defaultView = `<div style="white-space: pre-wrap; word-wrap: break-word; font-family: inherit; line-height: 1.6;">${escapeHtml(cleanText)}</div>`;
    }
    
    content.innerHTML = `
        <h3>📧 ${escapeHtml(email.subject)}</h3>
        <div class="email-meta">
            <p><strong>Dari:</strong> ${escapeHtml(email.from_email)}</p>
            <p><strong>Kepada:</strong> ${escapeHtml(email.to_email)}</p>
            <p><strong>Tanggal:</strong> ${formatDate(email.received_at, email.timestamp)}</p>
        </div>
        ${viewToggle}
        <div class="email-body" id="emailBodyContainer">
            ${defaultView}
        </div>
    `;
    
    // Store original content for toggle
    content.dataset.htmlContent = bodyContent;
    content.dataset.cleanContent = cleanText;
    content.dataset.isHtml = isHtml;
    content.dataset.isPartialHtml = isPartialHtml;
    
    modal.style.display = 'block';
}

// Render HTML email safely in iframe
function renderHtmlEmail(htmlContent) {
    // Create a safe iframe with unique ID
    const iframeId = 'emailIframe_' + Date.now();
    
    // Set up iframe after DOM is ready
    setTimeout(() => {
        const iframe = document.getElementById(iframeId);
        if (iframe) {
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            iframeDoc.open();
            iframeDoc.write(htmlContent);
            iframeDoc.close();
            
            // Auto-resize iframe based on content
            setTimeout(() => {
                try {
                    const body = iframeDoc.body;
                    const html = iframeDoc.documentElement;
                    const height = Math.max(
                        body.scrollHeight,
                        body.offsetHeight,
                        html.clientHeight,
                        html.scrollHeight,
                        html.offsetHeight
                    );
                    iframe.style.height = (height + 40) + 'px';
                } catch (e) {
                    // Fallback height if can't access iframe content
                    iframe.style.height = '600px';
                }
            }, 200);
        }
    }, 10);
    
    return `<iframe id="${iframeId}" sandbox="allow-same-origin allow-popups" style="width: 100%; border: 1px solid #ddd; border-radius: 8px; min-height: 400px; background: white; display: block;"></iframe>`;
}

// Toggle between HTML and text view
function toggleEmailView(view) {
    const container = document.getElementById('emailBodyContainer');
    const content = document.getElementById('emailContent');
    const htmlContent = content.dataset.htmlContent;
    const cleanContent = content.dataset.cleanContent;
    const isHtml = content.dataset.isHtml === 'true';
    const isPartialHtml = content.dataset.isPartialHtml === 'true';
    
    // Update button states
    const btnClean = document.getElementById('btnClean');
    const btnHtml = document.getElementById('btnHtml');
    const btnRaw = document.getElementById('btnRaw');
    
    if (btnClean) btnClean.classList.remove('active');
    if (btnHtml) btnHtml.classList.remove('active');
    if (btnRaw) btnRaw.classList.remove('active');
    
    if (view === 'clean') {
        if (btnClean) btnClean.classList.add('active');
        // For clean view, show text without HTML tags
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = htmlContent;
        const styleTags = tempDiv.querySelectorAll('style');
        styleTags.forEach(tag => tag.remove());
        const scriptTags = tempDiv.querySelectorAll('script');
        scriptTags.forEach(tag => tag.remove());
        let textOnly = tempDiv.textContent || tempDiv.innerText || cleanContent;
        textOnly = textOnly.replace(/\n\s*\n\s*\n/g, '\n\n').replace(/[ \t]+/g, ' ').replace(/^\s+/gm, '').trim();
        container.innerHTML = `<div style="white-space: pre-wrap; word-wrap: break-word; line-height: 1.6;">${escapeHtml(textOnly)}</div>`;
    } else if (view === 'html' && isHtml) {
        if (btnHtml) btnHtml.classList.add('active');
        
        // Wrap partial HTML in proper structure
        let htmlToRender = htmlContent;
        if (isPartialHtml || (!htmlContent.includes('<html') && !htmlContent.includes('<!DOCTYPE'))) {
            htmlToRender = `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            line-height: 1.6;
            color: #333;
        }
    </style>
</head>
<body>
${htmlContent}
</body>
</html>`;
        }
        
        container.innerHTML = renderHtmlEmail(htmlToRender);
    } else if (view === 'raw') {
        if (btnRaw) btnRaw.classList.add('active');
        container.innerHTML = `<pre style="white-space: pre-wrap; word-wrap: break-word; font-size: 12px;">${escapeHtml(htmlContent)}</pre>`;
    }
}

// Close modal
function closeModal() {
    document.getElementById('emailModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('emailModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Format date - menggunakan waktu lokal komputer
function formatDate(dateString, timestamp) {
    // Jika ada timestamp Unix, gunakan itu (lebih akurat)
    let date;
    if (timestamp) {
        date = new Date(timestamp * 1000); // Convert Unix timestamp to milliseconds
    } else {
        date = new Date(dateString);
    }
    
    // Konversi ke waktu lokal komputer pengguna
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return `${day}/${month}/${year} ${hours}:${minutes}`;
}

// Start auto refresh
function startAutoRefresh() {
    autoRefreshInterval = setInterval(refreshInbox, 10000); // Refresh every 10 seconds
}

// Stop auto refresh
function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
}

// Convert all timestamps to local time
function convertTimestampsToLocal() {
    const dateElements = document.querySelectorAll('.email-date[data-timestamp]');
    dateElements.forEach(element => {
        const timestamp = element.getAttribute('data-timestamp');
        const unixTimestamp = element.getAttribute('data-unix');
        if (timestamp) {
            element.textContent = formatDate(timestamp, unixTimestamp);
        }
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    convertTimestampsToLocal();
    startAutoRefresh();
});

// Stop auto refresh when page is hidden
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        stopAutoRefresh();
    } else {
        startAutoRefresh();
    }
});
