import { createRoot } from '@wordpress/element';

import SettingsApp from './SettingsApp';

const root = createRoot(document.getElementById('paysafe-admin-root'));
root.render(<SettingsApp />);
