import './bootstrap';

import Alpine from 'alpinejs';
import Clipboard from "@ryangjchandler/alpine-clipboard";
import 'flowbite';

Alpine.plugin(Clipboard);

window.Alpine = Alpine;

Alpine.start();
