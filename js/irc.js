$(() => {
    let active = true;
    let nickname = window.prompt('Enter your nickname');
    let color = randomColor();
    const chatbox = document.getElementById('chatbox');
    const input = document.getElementById('msginput');

    input.addEventListener('keydown', (e) => {
        let message = e.target.value;
        if (e.code === 'Enter' && nickname && message) {
            $.ajax({
                url: '/irc/send.php',
                method: 'POST',
                data: {
                    nickname,
                    message,
                    color,
                }
            });
            if (message === 'quit') {
                active = false;
            } else if (/^\/color ([0-9]|[a-f]|[A-F]){6}$/.test(message)) {
                color = message.substring(message.indexOf(' ') + 1);
            } else if (/^\/nick .+$/.test(message)) {
                nickname = message.substring(message.indexOf(' ') + 1);
            }
            e.target.value = '';

        }
    });

    function randomColor() {
        function color() {
            let hex = Math.floor(Math.random() * 256).toString(16);
            return (`0${hex}`).substr(-2);
        };
        return `${color()}${color()}${color()}`;
    }

    function addMessage({ nickname, color, message, time }) {
        let date = new Date(parseInt(time));
        let msg = document.createElement('div');
        msg.classList.add('message');
        let msgUser = document.createElement('span');
        msgUser.innerText = `${nickname} @ `;
        let msgTime = document.createElement('span');
        let messageContent = document.createElement('span');
        const lz = (num) => `0${num}`.substr(-2);
        msgTime.innerText = `${lz(date.getHours())}:${lz(date.getMinutes())}:${lz(date.getSeconds())} : `;
        messageContent.innerText = `${message}`;
        msgUser.style.setProperty(color, `#${color}`);
        messageContent.style.setProperty('color', `#${color}`);
        $(messageContent).emoticonize();
        msg.appendChild(msgUser);
        msg.appendChild(msgTime);
        msg.appendChild(messageContent);
        chatbox.appendChild(msg);
    }

    function addSystemMessage({ type, nickname, event_value, time }) {
        let date = new Date(parseInt(time));
        let msg = document.createElement('div');
        msg.classList.add('message');

        let messageText;
        switch (type) {
            case 'USER_QUIT':
                messageText = `SYSTEM: Użytkownik ${nickname} opuścił czat.`;
                break;

            case 'COLOR_CHANGE':
                messageText = `SYSTEM: Użytkownik ${nickname} zmienił kolor na ${event_value}.`;
                break;

            case 'NICKNAME_CHANGE':
                messageText = `SYSTEM: Użytkownik ${nickname} zmienił nick na ${event_value}.`;
                break;

            default:
                messageText = `SYSTEM: Zdarzenie ${type} dla użytkownika ${nickname} z wartością ${event_value} o ${time}`;
                break;
        }

        let messageContent = document.createElement('span');
        messageContent.innerText = `${messageText}`;
        msg.appendChild(messageContent);
        chatbox.appendChild(msg);
    }

    function pollMessage() {
        $.ajax({
            url: '/irc/ajax.php',
            method: 'POST',
            success(data) {
                if (data) {
                    addMessage(JSON.parse(data));
                }
            },
            complete() {
                if (active) {
                    pollMessage();
                }
            }
        });
    }

    function pollSystemMessage() {
        $.ajax({
            url: '/irc/ajax_system.php',
            method: 'POST',
            success(data) {
                if (data) {
                    console.log(data);
                    addSystemMessage(JSON.parse(data));
                }
            },
            complete() {
                if (active) {
                    pollSystemMessage();
                }
            }
        });
    }

    pollMessage();
    pollSystemMessage();
});