import './bootstrap';
import './echo';
import { joinProjectRealtime, leaveProjectRealtime } from './realtime/projectRealtime';

window.ProjectRealtime = {
	joinProjectRealtime,
	leaveProjectRealtime,
};
