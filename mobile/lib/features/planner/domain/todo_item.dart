import 'package:flutter/foundation.dart';

/// A student's own private task - stored on-device only (see
/// TodoLocalStore), never synced to the backend. Hand-written JSON
/// (de)serialization since this is the only model that needs it here - not
/// worth wiring build_runner codegen for one small class.
@immutable
class TodoItem {
  const TodoItem({
    required this.id,
    required this.title,
    this.notes,
    this.dueDate,
    this.done = false,
  });

  final String id;
  final String title;
  final String? notes;
  final DateTime? dueDate;
  final bool done;

  TodoItem copyWith({
    String? title,
    String? notes,
    DateTime? dueDate,
    bool clearDueDate = false,
    bool? done,
  }) => TodoItem(
    id: id,
    title: title ?? this.title,
    notes: notes ?? this.notes,
    dueDate: clearDueDate ? null : (dueDate ?? this.dueDate),
    done: done ?? this.done,
  );

  Map<String, dynamic> toJson() => {
    'id': id,
    'title': title,
    'notes': notes,
    'due_date': dueDate?.toIso8601String(),
    'done': done,
  };

  factory TodoItem.fromJson(Map<String, dynamic> json) => TodoItem(
    id: json['id'] as String,
    title: json['title'] as String,
    notes: json['notes'] as String?,
    dueDate: json['due_date'] == null
        ? null
        : DateTime.tryParse(json['due_date'] as String),
    done: json['done'] as bool? ?? false,
  );
}
