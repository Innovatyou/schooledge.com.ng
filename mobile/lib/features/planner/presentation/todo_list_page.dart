import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/widgets/depth_card.dart';
import '../../../core/widgets/module_ui.dart';
import '../data/todo_local_store.dart';
import '../domain/todo_item.dart';

class TodoListPage extends ConsumerWidget {
  const TodoListPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final todos = ref.watch(todoListProvider);
    final pending = todos.where((item) => !item.done).toList();
    final done = todos.where((item) => item.done).toList();
    return Scaffold(
      appBar: AppBar(title: const Text('My tasks')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _openEditor(context, ref),
        icon: const Icon(Icons.add_rounded),
        label: const Text('Add task'),
      ),
      body: todos.isEmpty
          ? const Center(
              child: Padding(
                padding: EdgeInsets.all(24),
                child: Text(
                  'No tasks yet. Add your own reminders for homework,\nrevision or anything else on your mind.',
                  textAlign: TextAlign.center,
                ),
              ),
            )
          : ListView(
              padding: const EdgeInsets.fromLTRB(20, 20, 20, 100),
              children: [
                if (pending.isEmpty)
                  const InfoRow(
                    icon: Icons.celebration_rounded,
                    title: 'All caught up!',
                    subtitle: 'Nothing pending right now.',
                    color: Color(0xff00a896),
                    trailing: SizedBox.shrink(),
                  )
                else
                  ...pending.map(
                    (item) => _TodoRow(
                      item: item,
                      onToggle: () => ref
                          .read(todoListProvider.notifier)
                          .toggleDone(item.id),
                      onTap: () => _openEditor(context, ref, existing: item),
                    ),
                  ),
                if (done.isNotEmpty) ...[
                  const SectionTitle('Completed'),
                  ...done.map(
                    (item) => _TodoRow(
                      item: item,
                      onToggle: () => ref
                          .read(todoListProvider.notifier)
                          .toggleDone(item.id),
                      onTap: () => _openEditor(context, ref, existing: item),
                    ),
                  ),
                ],
              ],
            ),
    );
  }

  Future<void> _openEditor(
    BuildContext context,
    WidgetRef ref, {
    TodoItem? existing,
  }) => showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    showDragHandle: true,
    builder: (sheetContext) => Padding(
      padding: EdgeInsets.only(
        left: 20,
        right: 20,
        top: 8,
        bottom: MediaQuery.viewInsetsOf(sheetContext).bottom + 20,
      ),
      child: _TodoEditor(ref: ref, existing: existing),
    ),
  );
}

class _TodoRow extends StatelessWidget {
  const _TodoRow({
    required this.item,
    required this.onToggle,
    required this.onTap,
  });
  final TodoItem item;
  final VoidCallback onToggle;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.only(bottom: 12),
    child: DepthCard(
      onTap: onTap,
      padding: const EdgeInsets.all(15),
      child: Row(
        children: [
          IconButton(
            onPressed: onToggle,
            icon: Icon(
              item.done ? Icons.check_circle_rounded : Icons.circle_outlined,
              color: item.done
                  ? const Color(0xff00a896)
                  : const Color(0xff829ab1),
            ),
          ),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item.title,
                  style: TextStyle(
                    fontWeight: FontWeight.w900,
                    decoration: item.done ? TextDecoration.lineThrough : null,
                    color: item.done
                        ? Theme.of(context).colorScheme.onSurfaceVariant
                        : Theme.of(context).colorScheme.onSurface,
                  ),
                ),
                if (item.dueDate != null)
                  Text(
                    'Due ${DateFormat('EEE, d MMM').format(item.dueDate!)}',
                    style: const TextStyle(
                      color: Color(0xff829ab1),
                      fontSize: 12,
                    ),
                  ),
                if ((item.notes ?? '').isNotEmpty)
                  Text(
                    item.notes!,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: Color(0xff627d98),
                      fontSize: 12,
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    ),
  );
}

class _TodoEditor extends StatefulWidget {
  const _TodoEditor({required this.ref, this.existing});
  final WidgetRef ref;
  final TodoItem? existing;

  @override
  State<_TodoEditor> createState() => _TodoEditorState();
}

class _TodoEditorState extends State<_TodoEditor> {
  late final _title = TextEditingController(text: widget.existing?.title);
  late final _notes = TextEditingController(text: widget.existing?.notes);
  DateTime? _dueDate;

  @override
  void initState() {
    super.initState();
    _dueDate = widget.existing?.dueDate;
  }

  @override
  void dispose() {
    _title.dispose();
    _notes.dispose();
    super.dispose();
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _dueDate ?? DateTime.now(),
      firstDate: DateTime.now().subtract(const Duration(days: 1)),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (picked != null) setState(() => _dueDate = picked);
  }

  void _save() {
    final title = _title.text.trim();
    if (title.isEmpty) return;
    final notifier = widget.ref.read(todoListProvider.notifier);
    if (widget.existing == null) {
      notifier.add(title, notes: _notes.text.trim(), dueDate: _dueDate);
    } else {
      notifier.update(
        widget.existing!.id,
        title: title,
        notes: _notes.text.trim(),
        dueDate: _dueDate,
        clearDueDate: _dueDate == null,
      );
    }
    Navigator.of(context).pop();
  }

  void _delete() {
    if (widget.existing != null) {
      widget.ref.read(todoListProvider.notifier).remove(widget.existing!.id);
    }
    Navigator.of(context).pop();
  }

  @override
  Widget build(BuildContext context) => Column(
    mainAxisSize: MainAxisSize.min,
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      Text(
        widget.existing == null ? 'New task' : 'Edit task',
        style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w900),
      ),
      const SizedBox(height: 16),
      TextField(
        controller: _title,
        autofocus: true,
        decoration: const InputDecoration(labelText: 'Title'),
      ),
      const SizedBox(height: 12),
      TextField(
        controller: _notes,
        maxLines: 2,
        decoration: const InputDecoration(labelText: 'Notes (optional)'),
      ),
      const SizedBox(height: 12),
      Row(
        children: [
          Expanded(
            child: OutlinedButton.icon(
              onPressed: _pickDate,
              icon: const Icon(Icons.calendar_today_rounded),
              label: Text(
                _dueDate == null
                    ? 'Set due date'
                    : DateFormat('EEE, d MMM').format(_dueDate!),
              ),
            ),
          ),
          if (_dueDate != null)
            IconButton(
              onPressed: () => setState(() => _dueDate = null),
              icon: const Icon(Icons.close_rounded),
            ),
        ],
      ),
      const SizedBox(height: 20),
      Row(
        children: [
          if (widget.existing != null)
            Expanded(
              child: OutlinedButton.icon(
                onPressed: _delete,
                icon: const Icon(Icons.delete_outline_rounded),
                label: const Text('Delete'),
                style: OutlinedButton.styleFrom(
                  foregroundColor: const Color(0xffd64545),
                ),
              ),
            ),
          if (widget.existing != null) const SizedBox(width: 12),
          Expanded(
            child: FilledButton(onPressed: _save, child: const Text('Save')),
          ),
        ],
      ),
    ],
  );
}
