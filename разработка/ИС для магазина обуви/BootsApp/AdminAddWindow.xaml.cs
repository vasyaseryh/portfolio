using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;
using System.Windows.Documents;
using System.Windows.Input;
using System.Windows.Media;
using System.Windows.Media.Imaging;
using System.Windows.Shapes;

namespace BootsApp
{
    /// <summary>
    /// Логика взаимодействия для AdminAddWindow.xaml
    /// </summary>
    public partial class AdminAddWindow : Window
    {
        public bootsEntities2 _context;
        public bool isEdit;
        public user_import user;
        public AdminAddWindow()
        {
            InitializeComponent();
            isEdit = false;
            _context = bootsEntities2.GetContext();
            Role.ItemsSource = _context.роли.ToList();
            Role.SelectedItem = Role.Items[2];
           
        }

        public AdminAddWindow(user_import items)
        {
            user = items;
            isEdit = true;
            InitializeComponent();
            _context = bootsEntities2.GetContext();
            DataContext = items;
            Role.ItemsSource = _context.роли.ToList();
        }

        private void AddButt(object sender, RoutedEventArgs e)
        {
            if (!isEdit)
            {
                _context.user_import.Add(new user_import
                {
                    id_роль = (int)Role.SelectedValue,
                    ФИО = ФИО.Text,
                    Логин = Login.Text,
                    Пароль = Password.Text
                });
                _context.SaveChanges();
            }
            else 
            {
                _context.SaveChanges();
            }
            this.Close();
        }
    }
}
