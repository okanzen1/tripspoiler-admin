import './bootstrap';
import './image-compress';
import './wizard';
import './toggle';
import './slug';
import './inline-slug';
import './image-upload';
import './mailbox';
import './activity-form';
import './repeater';
import './faq-bulk';
import './dropdown';
import './sortable-cards';
import './page-sections';
import './confirm-dialog';
import './live-refresh';
import './translation-jobs';

// Zengin metin editörü sadece gereken sayfalarda yüklensin
if (document.querySelector('[data-rich-editor]')) {
    import('./rich-editor');
}
// Grafik kütüphanesi sadece analitik sayfasında yüklensin
if (document.getElementById('analytics-chart')) {
    import('./analytics-chart');
}
