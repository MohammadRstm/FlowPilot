export const typingBarrier = {
  active: 0,
  resolvers: [] as (() => void)[],

  start() {
    this.active++;
  },

  done() {
    this.active--;
    if (this.active <= 0) {
      this.active = 0;
      this.resolvers.forEach(r => r());
      this.resolvers = [];
    }
  },

  wait() {
    if (this.active === 0) return Promise.resolve();
    return new Promise<void>(res => this.resolvers.push(res));
  }
};
