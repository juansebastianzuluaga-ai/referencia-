import { toast } from 'vue3-toastify';

export const notify = {
  success(msg: string) {
    toast.success(msg, { autoClose: 3000, theme: 'colored', position: 'top-right' });
  },
  error(msg: string) {
    toast.error(msg, { autoClose: 4000, theme: 'colored', position: 'top-right' });
  },
  info(msg: string) {
    toast.info(msg, { autoClose: 3000, theme: 'colored', position: 'top-right' });
  },
  warning(msg: string) {
    toast.warning(msg, { autoClose: 3500, theme: 'colored', position: 'top-right' });
  },
};

export default notify;
