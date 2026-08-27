import 'package:flutter/material.dart';
import '../../../core/widgets/module_ui.dart';

class MessagesPage extends StatelessWidget {
  const MessagesPage({super.key, this.embedded = false});
  final bool embedded;
  @override
  Widget build(BuildContext context) {
    final content = [
      TextField(
        decoration: InputDecoration(
          hintText: 'Search conversations',
          prefixIcon: const Icon(Icons.search_rounded),
          filled: true,
          fillColor: Colors.white,
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(18),
            borderSide: BorderSide.none,
          ),
        ),
      ),
      const SizedBox(height: 18),
      const SectionTitle('Conversations'),
      InfoRow(
        icon: Icons.person_rounded,
        title: 'Mrs. Adeyemi',
        subtitle: 'Your mathematics assignment was received.',
        color: const Color(0xff725cff),
        trailing: const _UnreadBadge('2'),
        onTap: () => _openChat(context, 'Mrs. Adeyemi'),
      ),
      InfoRow(
        icon: Icons.groups_rounded,
        title: 'JSS 1 Announcements',
        subtitle: 'Inter-house sports registration closes Friday.',
        color: const Color(0xffff8a5b),
        onTap: () => _openChat(context, 'JSS 1 Announcements'),
      ),
      InfoRow(
        icon: Icons.support_agent_rounded,
        title: 'School Administration',
        subtitle: 'Fee payment reminder for third term.',
        color: const Color(0xff168aad),
        onTap: () => _openChat(context, 'School Administration'),
      ),
    ];
    if (embedded) {
      return ListView(
        padding: const EdgeInsets.fromLTRB(20, 20, 20, 120),
        children: [
          const Text(
            'Messages',
            style: TextStyle(
              fontSize: 30,
              fontWeight: FontWeight.w900,
              color: Color(0xff102a43),
            ),
          ),
          const SizedBox(height: 6),
          const Text(
            'Stay connected with your school.',
            style: TextStyle(color: Color(0xff627d98)),
          ),
          const SizedBox(height: 20),
          ...content,
        ],
      );
    }
    return ModulePage(
      title: 'Messages',
      subtitle: 'Private conversations and school announcements.',
      icon: Icons.forum_rounded,
      colors: const [Color(0xffb83b96), Color(0xffe66bb2)],
      children: content,
    );
  }

  void _openChat(BuildContext context, String name) => Navigator.of(
    context,
  ).push(MaterialPageRoute(builder: (_) => _ChatPage(name: name)));
}

class _UnreadBadge extends StatelessWidget {
  const _UnreadBadge(this.count);
  final String count;
  @override
  Widget build(BuildContext context) => CircleAvatar(
    radius: 13,
    backgroundColor: const Color(0xffd65db1),
    child: Text(
      count,
      style: const TextStyle(
        color: Colors.white,
        fontSize: 11,
        fontWeight: FontWeight.w900,
      ),
    ),
  );
}

class _ChatPage extends StatefulWidget {
  const _ChatPage({required this.name});
  final String name;
  @override
  State<_ChatPage> createState() => _ChatPageState();
}

class _ChatPageState extends State<_ChatPage> {
  final controller = TextEditingController();
  final messages = <String>[];
  @override
  void dispose() {
    controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(
      title: Text(widget.name),
      actions: [
        IconButton(
          onPressed: () => showModuleMessage(context, 'Conversation details'),
          icon: const Icon(Icons.info_outline_rounded),
        ),
      ],
    ),
    body: Column(
      children: [
        Expanded(
          child: ListView(
            padding: const EdgeInsets.all(20),
            children: [
              const Align(
                alignment: Alignment.centerLeft,
                child: _Bubble(
                  'Hello! Your latest school update is available.',
                  false,
                ),
              ),
              const Align(
                alignment: Alignment.centerRight,
                child: _Bubble('Thank you, I have seen it.', true),
              ),
              ...messages.map(
                (text) => Align(
                  alignment: Alignment.centerRight,
                  child: _Bubble(text, true),
                ),
              ),
            ],
          ),
        ),
        SafeArea(
          top: false,
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: controller,
                    decoration: const InputDecoration(
                      hintText: 'Write a message…',
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                IconButton.filled(
                  onPressed: () {
                    if (controller.text.trim().isEmpty) return;
                    setState(() {
                      messages.add(controller.text.trim());
                      controller.clear();
                    });
                  },
                  icon: const Icon(Icons.send_rounded),
                ),
              ],
            ),
          ),
        ),
      ],
    ),
  );
}

class _Bubble extends StatelessWidget {
  const _Bubble(this.text, this.mine);
  final String text;
  final bool mine;
  @override
  Widget build(BuildContext context) => Container(
    margin: const EdgeInsets.only(bottom: 12),
    padding: const EdgeInsets.all(14),
    constraints: const BoxConstraints(maxWidth: 280),
    decoration: BoxDecoration(
      color: mine ? const Color(0xff163a70) : Colors.white,
      borderRadius: BorderRadius.circular(18),
    ),
    child: Text(
      text,
      style: TextStyle(color: mine ? Colors.white : const Color(0xff102a43)),
    ),
  );
}
