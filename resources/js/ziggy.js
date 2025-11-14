const Ziggy = {"url":"http:\/\/localhost","port":null,"defaults":{},"routes":{"home":{"uri":"\/","methods":["GET","HEAD"]},"Welcome":{"uri":"test","methods":["GET","HEAD"]},"authloginredirect":{"uri":"auth\/login\/redirect","methods":["GET","HEAD"]},"authlogincallback":{"uri":"auth\/login\/callback","methods":["GET","HEAD"]},"authregister":{"uri":"auth\/register","methods":["GET","HEAD"]},"authvalidate":{"uri":"auth\/validate","methods":["POST"]},"storage.local":{"uri":"storage\/{path}","methods":["GET","HEAD"],"wheres":{"path":".*"},"parameters":["path"]}}};
if (typeof window !== 'undefined' && typeof window.Ziggy !== 'undefined') {
  Object.assign(Ziggy.routes, window.Ziggy.routes);
}
export { Ziggy };
